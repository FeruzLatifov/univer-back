<?php

namespace App\DTO\Menu;

/**
 * Menu Item Data Transfer Object
 *
 * Immutable data structure for menu items
 * No business logic, just data transfer
 */
class MenuItemDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $url,
        public readonly string $icon,
        public readonly ?string $permission = null,
        public readonly array $items = [],
        public readonly bool $active = true,
        public readonly ?int $order = null,
    ) {}

    /**
     * Convert to array (for API responses)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'url' => $this->url,
            'icon' => $this->icon,
            'permission' => $this->permission,
            'items' => array_map(
                fn(MenuItemDTO $item) => $item->toArray(),
                $this->items
            ),
            'active' => $this->active,
            'order' => $this->order,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            label: $data['label'] ?? '',
            url: $data['url'] ?? '',
            icon: $data['icon'] ?? 'circle',
            permission: $data['permission'] ?? null,
            items: array_map(
                fn($item) => self::fromArray($item),
                $data['items'] ?? []
            ),
            active: $data['active'] ?? true,
            order: $data['order'] ?? null,
        );
    }

    /**
     * Check if menu item has children
     */
    public function hasItems(): bool
    {
        return count($this->items) > 0;
    }

    /**
     * Filter child items by permission
     */
    public function filterItems(array $permissions): self
    {
        $filteredItems = array_filter(
            $this->items,
            fn(MenuItemDTO $item) => $this->canAccess($item, $permissions)
        );

        return new self(
            id: $this->id,
            label: $this->label,
            url: $this->url,
            icon: $this->icon,
            permission: $this->permission,
            items: array_map(
                fn(MenuItemDTO $item) => $item->filterItems($permissions),
                $filteredItems
            ),
            active: $this->active,
            order: $this->order,
        );
    }

    /**
     * Check if user can access this menu item
     */
    private function canAccess(MenuItemDTO $item, array $permissions): bool
    {
        // No permission required
        if ($item->permission === null) {
            return true;
        }

        // Admin wildcard
        if (in_array('*', $permissions)) {
            return true;
        }

        // Exact match
        if (in_array($item->permission, $permissions)) {
            return true;
        }

        // Wildcard pattern (e.g., 'student.*' matches 'student.view')
        foreach ($permissions as $p) {
            if (str_ends_with($p, '.*')) {
                $prefix = substr($p, 0, -2);
                if (str_starts_with($item->permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }
}
