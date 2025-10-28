<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student Resource (API V1)
 *
 * Transform student data for API responses
 */
class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'student',
            'student_id_number' => $this->student_id_number,
            'first_name' => $this->first_name,
            'second_name' => $this->second_name,
            'third_name' => $this->third_name,
            'full_name' => $this->full_name,
            'birth_date' => $this->birth_date,
            'gender' => $this->_gender,
            'phone' => $this->phone_number,
            'email' => $this->email,
            'image' => $this->image,
            'active' => (bool) $this->active,

            // Conditional relationships
            'meta' => $this->whenLoaded('meta', function () {
                return [
                    'id' => $this->meta->id,
                    'student_status' => $this->meta->_student_status,
                    'education_type' => $this->meta->_education_type,
                    'education_form' => $this->meta->_education_form,
                    'level' => $this->meta->_level,
                    'course' => $this->meta->_curriculum?->_education_year,
                    'specialty' => $this->whenLoaded('meta.specialty', [
                        'code' => $this->meta->specialty->code,
                        'name' => $this->meta->specialty->name,
                    ]),
                    'group' => $this->whenLoaded('meta.group', [
                        'name' => $this->meta->group->name,
                    ]),
                ];
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
