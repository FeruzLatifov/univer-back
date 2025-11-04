<?php

namespace App\Contracts\Services;

use App\DTO\Menu\MenuResponseDTO;
use App\Models\EAdmin;

/**
 * Menu Service Interface
 *
 * Defines contract for menu business logic
 * Separates business logic from controllers (Single Responsibility)
 */
interface MenuServiceInterface
{
    /**
     * Get filtered menu for authenticated user
     *
     * @param EAdmin $user
     * @param string|null $locale
     * @return MenuResponseDTO
     */
    public function getMenuForUser(EAdmin $user, ?string $locale = null): MenuResponseDTO;

    /**
     * Check if user can access a path
     *
     * @param EAdmin $user
     * @param string $path
     * @return bool
     */
    public function canUserAccessPath(EAdmin $user, string $path): bool;

    /**
     * Invalidate menu cache for user
     *
     * @param EAdmin $user
     * @return bool
     */
    public function invalidateUserMenuCache(EAdmin $user): bool;

    /**
     * Get menu structure (unfiltered, for admin panel)
     *
     * @return array
     */
    public function getMenuStructure(): array;
}
