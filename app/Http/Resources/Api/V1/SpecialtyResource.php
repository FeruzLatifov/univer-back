<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Specialty Resource (API V1)
 *
 * Transform specialty data for API responses
 */
class SpecialtyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'education_type' => $this->_education_type,
            'active' => (bool) $this->active,

            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'code' => $this->department->code,
                    'name' => $this->department->name,
                ];
            }),

            'groups_count' => $this->when(
                $this->relationLoaded('groups'),
                fn() => $this->groups->count()
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
