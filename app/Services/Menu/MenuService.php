<?php

namespace App\Services\Menu;

use App\Contracts\Repositories\MenuRepositoryInterface;
use App\Contracts\Services\MenuServiceInterface;
use App\DTO\Menu\MenuItemDTO;
use App\DTO\Menu\MenuResponseDTO;
use App\Models\EAdmin;
use App\Services\Translation\MultiTenantTranslationService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Menu Service
 *
 * Business logic for menu generation and filtering
 * No direct database access - uses repository (Dependency Inversion)
 *
 * @microservice-ready Can be extracted to separate service with API calls
 */
class MenuService implements MenuServiceInterface
{
    public function __construct(
        protected MenuRepositoryInterface $menuRepository
    ) {}

    /**
     * Get filtered menu for authenticated user
     */
    public function getMenuForUser(EAdmin $user, ?string $locale = null): MenuResponseDTO
    {
        $locale = $locale ?? App::getLocale();

        // ✅ FIX: Pass roleId to cache to prevent wrong menu after role switch
        $roleId = $user->_role;

        // Try to get from cache
        $cachedMenu = $this->menuRepository->getCachedMenu($user->id, $locale, $roleId);

        if ($cachedMenu !== null) {
            Log::info("[MenuService] Cache hit for user {$user->id}");

            return new MenuResponseDTO(
                menu: array_map(fn($item) => MenuItemDTO::fromArray($item), $cachedMenu['menu']),
                permissions: $cachedMenu['permissions'],
                locale: $locale,
                cached: true,
                cacheExpiresAt: $cachedMenu['expires_at'] ?? null,
            );
        }

        Log::info("[MenuService] Cache miss for user {$user->id}, generating menu");

        // Get user permissions
        $permissions = $user->getAllPermissions();

        // Get menu structure
        $menuConfig = $this->menuRepository->getMenuConfig();

        // Filter menu by permissions
        $filteredMenu = $this->filterMenuByPermissions($menuConfig, $permissions, $locale);

        // Prepare response
        $response = new MenuResponseDTO(
            menu: $filteredMenu,
            permissions: $permissions,
            locale: $locale,
            cached: false,
            cacheExpiresAt: now()->addSeconds($this->menuRepository->getCacheTTL())->timestamp,
        );

        // Cache for next time
        // ✅ FIX: Pass roleId to cache to prevent wrong menu after role switch
        $this->menuRepository->cacheMenu(
            $user->id,
            $locale,
            [
                'menu' => array_map(fn(MenuItemDTO $item) => $item->toArray(), $filteredMenu),
                'permissions' => $permissions,
                'expires_at' => $response->cacheExpiresAt,
            ],
            $this->menuRepository->getCacheTTL(),
            $roleId
        );

        return $response;
    }

    /**
     * Filter menu structure by user permissions
     *
     * @param array $menuConfig
     * @param array $permissions
     * @param string $locale
     * @return array<MenuItemDTO>
     */
    protected function filterMenuByPermissions(array $menuConfig, array $permissions, string $locale): array
    {
        $filtered = [];

        foreach ($menuConfig as $groupId => $group) {
            $menuItem = $this->buildMenuItem($groupId, $group, $permissions, $locale);

            // Only include if user has permission or if it has accessible children
            if ($menuItem && ($this->canAccessMenuItem($menuItem, $permissions) || $menuItem->hasItems())) {
                $filtered[] = $menuItem;
            }
        }

        // Stable ordering to match Yii2 behavior:
        // - Items with explicit 'order' are sorted ascending
        // - Items without 'order' keep original insertion order
        if (!empty($filtered)) {
            $withOrder = [];
            $withoutOrder = [];

            foreach ($filtered as $item) {
                if ($item->order !== null) {
                    $withOrder[] = $item;
                } else {
                    $withoutOrder[] = $item;
                }
            }

            if (count($withOrder) > 1) {
                usort($withOrder, function (MenuItemDTO $a, MenuItemDTO $b) {
                    return ($a->order ?? 0) <=> ($b->order ?? 0);
                });
            }

            $filtered = array_merge($withOrder, $withoutOrder);
        }

        return $filtered;
    }

    /**
     * Build menu item from config
     */
    protected function buildMenuItem(string $id, array $config, array $permissions, string $locale): ?MenuItemDTO
    {
        // Filter child items first
        $childItems = [];

        if (isset($config['items']) && is_array($config['items'])) {
            foreach ($config['items'] as $childId => $childConfig) {
                $childItem = $this->buildMenuItem($childId, $childConfig, $permissions, $locale);

                if ($childItem && $this->canAccessMenuItem($childItem, $permissions)) {
                    $childItems[] = $childItem;
                }
            }
        }

        // Preserve child insertion order from config unless explicit 'order' is provided
        // If children have 'order', sort only those while keeping others in original order
        if (!empty($childItems)) {
            $ordered = [];
            $unordered = [];

            foreach ($childItems as $ci) {
                if ($ci->order !== null) {
                    $ordered[] = $ci;
                } else {
                    $unordered[] = $ci; // already in insertion order
                }
            }

            if (count($ordered) > 1) {
                usort($ordered, function (MenuItemDTO $a, MenuItemDTO $b) {
                    return ($a->order ?? 0) <=> ($b->order ?? 0);
                });
            }

            // Merge: explicit orders first (like Yii2 numeric order), then original-order rest
            $childItems = array_merge($ordered, $unordered);
        }

        // If no accessible children and parent requires permission, skip
        if (empty($childItems) && isset($config['permission']) && !$this->hasPermission($config['permission'], $permissions)) {
            return null;
        }

        // Build item
        $rawUrl = $config['url'] ?? '#';
        $normalizedUrl = $this->normalizeUrl($id, $rawUrl);
        return new MenuItemDTO(
            id: $id,
            label: $this->translateLabel($config['label'] ?? $id, $locale),
            url: $normalizedUrl,
            icon: $config['icon'] ?? 'circle',
            permission: $config['permission'] ?? null,
            items: $childItems,
            active: $config['active'] ?? true,
            order: $config['order'] ?? null,
        );
    }

    /**
     * Check if user can access menu item
     */
    protected function canAccessMenuItem(MenuItemDTO $item, array $permissions): bool
    {
        // No permission required
        if ($item->permission === null) {
            return true;
        }

        // Primary check: dot-notation permission (e.g., 'student.view', wildcards)
        if ($this->hasPermission($item->permission, $permissions)) {
            return true;
        }

        // Legacy fallback: route-based permissions from Yii2 (e.g., 'student/student')
        // If user has a path-like permission, allow access when it matches item id or url prefix
        $itemIdPath = trim($item->id, '/');
        $itemUrlPath = ltrim(parse_url($item->url, PHP_URL_PATH) ?: '', '/');

        foreach ($permissions as $p) {
            if (str_contains($p, '/')) {
                $permPath = trim($p, '/');
                if (
                    ($itemUrlPath !== '' && str_starts_with($itemUrlPath, $permPath)) ||
                    ($itemIdPath !== '' && str_starts_with($itemIdPath, $permPath))
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has permission
     */
    protected function hasPermission(string $permission, array $permissions): bool
    {
        // Admin wildcard
        if (in_array('*', $permissions)) {
            return true;
        }

        // Exact match
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Wildcard pattern (e.g., 'student.*' matches 'student.view')
        foreach ($permissions as $p) {
            if (str_ends_with($p, '.*')) {
                $prefix = substr($p, 0, -2);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        // Permission aliases (bridge between route and dot permissions)
        $aliases = config('permission_aliases.map', []);
        if (isset($aliases[$permission]) && is_array($aliases[$permission])) {
            foreach ($aliases[$permission] as $alt) {
                if (in_array($alt, $permissions, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Translate label
     *
     * Uses HYBRID approach:
     * 1. Try MultiTenantTranslationService (supports university overrides)
     * 2. Fallback to Laravel trans() (base translations)
     *
     * Performance:
     * - First request: ~17ms (load base + overrides from DB)
     * - Subsequent: ~0.002ms (from cache)
     *
     * How it works:
     * - Base translations from files (lang/{locale}/menu.php) via opcache
     * - University-specific overrides from DB (cached per university)
     * - Result merged and cached for 1 hour
     */
    protected function translateLabel(string $label, string $locale): string
    {
        try {
            // Use MultiTenantTranslationService for university-specific translations
            // This supports DB overrides while still using file-based cache
            $translationService = app(MultiTenantTranslationService::class, [
                'locale' => $locale,
                'universityId' => config('app.university_id'),
            ]);

            $translated = $translationService->trans($label, 'menu');

            // If translation not found, return original label
            if ($translated === $label) {
                return $label;
            }

            return $translated;
        } catch (\Exception $e) {
            // Fallback to standard Laravel trans() if service fails
            Log::warning("[MenuService] Translation service failed, using fallback", [
                'label' => $label,
                'locale' => $locale,
                'error' => $e->getMessage(),
            ]);

            $translated = trans('menu.' . $label, [], $locale);

            if ($translated === 'menu.' . $label) {
                return $label;
            }

            return $translated;
        }
    }

    /**
     * Check if user can access a path
     */
    public function canUserAccessPath(EAdmin $user, string $path): bool
    {
        if (!$user->role) {
            return false;
        }

        return $this->menuRepository->isPathAccessible($path, $user->role);
    }

    /**
     * Invalidate menu cache for user
     */
    public function invalidateUserMenuCache(EAdmin $user): bool
    {
        Log::info("[MenuService] Invalidating menu cache for user {$user->id}");

        // Invalidate legacy keys (without role) via repository
        $this->menuRepository->invalidateMenuCache($user->id);

        // Also invalidate role-scoped keys to avoid stale menus after role switch
        $locales = ['uz', 'oz', 'ru', 'en'];
        $roleId = $user->_role;
        foreach ($locales as $locale) {
            $cacheKey = 'menu:user:' . $user->id . ':role:' . $roleId . ':locale:' . $locale;
            Cache::forget($cacheKey);
        }

        return true;
    }

    /**
     * Normalize/Map legacy URLs to current frontend routes
     */
    private function normalizeUrl(string $id, string $url): string
    {
        $cleanId = ltrim($id, '/');
        $cleanUrl = ltrim($url, '/');

        // If preserving legacy paths, do not map – return as-is (with single leading slash)
        if (Config::get('menu_settings.preserve_legacy_paths', true)) {
            return '/' . $cleanUrl;
        }

        // Map by exact id first, then by url
        $map = Config::get('menu_routes.exact', []);

        if (isset($map[$cleanId])) {
            return $map[$cleanId];
        }

        if (isset($map[$cleanUrl])) {
            return $map[$cleanUrl];
        }

        // Ensure leading slash and collapse to single slash
        $normalized = '/' . $cleanUrl;
        $normalized = preg_replace('#/{2,}#', '/', $normalized);

        return $normalized;
    }

    /**
     * Get menu structure (unfiltered, for admin panel)
     */
    public function getMenuStructure(): array
    {
        return $this->menuRepository->getMenuConfig();
    }
}
