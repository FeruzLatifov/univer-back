<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use App\Models\EAdminRole;

/**
 * Admin Resource (API V1)
 *
 * Transform admin/staff data for API responses
 */
class AdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $rolesCollection = collect();
        if (Schema::hasTable('e_admin_roles')) {
            $rolesCollection = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();
        }

        $activeRole = null;
        try {
            $activeRole = $this->relationLoaded('role') ? $this->role : $this->role()->first();
        } catch (\Throwable $e) {
            $activeRole = null;
        }

        if ($rolesCollection->isEmpty() && $activeRole) {
            $rolesCollection = collect([$activeRole]);
        }

        // ZERO TRUST APPROACH:
        // Permissions are NOT included in login response (keeps response small ~2KB)
        // Frontend must call GET /auth/permissions separately
        // This prevents JWT token bloat (100+ permissions = 8-15KB)
        //
        // Optional: include permissions if explicitly requested
        $includePermissions = request()->has('include_permissions')
            && request()->boolean('include_permissions');

        $permissions = [];
        if ($includePermissions) {
            try {
                if (method_exists($this->resource, 'getAllPermissions')) {
                    $permissions = $this->resource->getAllPermissions();
                }
            } catch (\Throwable $e) {
                logger()->warning('[AdminResource] Failed to get permissions', [
                    'user_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $languageShort = $this->mapLanguageToShort($this->language);

        return [
            'id' => $this->id,
            'type' => 'admin',
            'login' => $this->login,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->telephone,
            'language' => $languageShort,
            'role' => $activeRole?->code ?? ($activeRole?->id ? (string) $activeRole->id : ($this->_role ? (string) $this->_role : null)),
            'role_id' => $activeRole?->id ?? $this->_role,
            'role_code' => $activeRole?->code,
            'role_name' => $activeRole?->display_name ?? $activeRole?->name,
            'roles' => $rolesCollection
                ->filter()
                ->map(fn ($role) => [
                    'id' => $role->id,
                    'code' => $role->code,
                    'name' => method_exists($role, 'getDisplayNameAttribute') ? $role->display_name : ($role->name ?? $role->code),
                ])
                ->values(),
            // Permissions only included if explicitly requested
            // Use GET /auth/permissions endpoint instead
            'permissions' => $includePermissions ? $permissions : null,
            'status' => $this->status,
            'active' => $this->status === 'enable', // Map status to active for frontend
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'full_name' => $this->employee->full_name,
                    'department' => null, // Can be extended with department info if needed
                    'image' => $this->employee->image,
                    'avatar_url' => $this->employee->image,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function mapLanguageToShort(?string $language): ?string
    {
        if (!$language) {
            return app()->getLocale();
        }

        $map = [
            'uz-UZ' => 'uz',
            'oz-UZ' => 'oz',
            'ru-RU' => 'ru',
            'en-US' => 'en',
        ];

        return $map[$language] ?? $language;
    }
}
