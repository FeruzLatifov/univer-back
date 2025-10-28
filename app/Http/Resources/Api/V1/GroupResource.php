<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Group Resource (API V1)
 *
 * Transform group data for API responses
 */
class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'education_type' => $this->_education_type,
            'education_form' => $this->_education_form,
            'education_year' => $this->_education_year,
            'level' => $this->_level,
            'active' => (bool) $this->active,

            // Relationships
            'specialty' => $this->whenLoaded('specialty', function () {
                return [
                    'id' => $this->specialty->id,
                    'code' => $this->specialty->code,
                    'name' => $this->specialty->name,
                ];
            }),

            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => $this->department->id,
                    'code' => $this->department->code,
                    'name' => $this->department->name,
                ];
            }),

            'students_count' => $this->when(
                $this->relationLoaded('students'),
                fn() => $this->students->count()
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
