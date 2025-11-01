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

        return [
            'id' => $this->id,
            'type' => 'admin',
            'login' => $this->login,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->telephone,
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
}
