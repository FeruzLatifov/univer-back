<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'id' => $this->id,
            'type' => 'admin',
            'login' => $this->login,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->telephone,
            'role' => $this->_role,
            'status' => $this->status,
            'active' => (bool) $this->active,
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'full_name' => $this->employee->full_name,
                    'department' => $this->employee->structure->name ?? null,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
