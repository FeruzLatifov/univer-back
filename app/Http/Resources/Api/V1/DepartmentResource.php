<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Department Resource (API V1)
 *
 * Transform department data for API responses
 */
class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'structure_type' => $this->_structure_type,
            'active' => (bool) $this->active,

            'parent' => $this->whenLoaded('parent', function () {
                return $this->parent ? [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                    'code' => $this->parent->code,
                ] : null;
            }),

            'children_count' => $this->when(
                $this->relationLoaded('children'),
                fn() => $this->children->count()
            ),

            'specialties_count' => $this->when(
                $this->relationLoaded('specialties'),
                fn() => $this->specialties->count()
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
