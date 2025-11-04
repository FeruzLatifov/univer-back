<?php

namespace App\DTO\Menu;

/**
 * Menu Response Data Transfer Object
 *
 * Complete API response structure for menu
 */
class MenuResponseDTO
{
    public function __construct(
        public readonly array $menu,
        public readonly array $permissions,
        public readonly string $locale,
        public readonly bool $cached = false,
        public readonly ?int $cacheExpiresAt = null,
    ) {}

    /**
     * Convert to array (for JSON API response)
     */
    public function toArray(): array
    {
        return [
            'success' => true,
            'data' => [
                'menu' => array_map(
                    fn(MenuItemDTO $item) => $item->toArray(),
                    $this->menu
                ),
                'permissions' => $this->permissions,
                'locale' => $this->locale,
            ],
            'meta' => [
                'cached' => $this->cached,
                'cache_expires_at' => $this->cacheExpiresAt,
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
