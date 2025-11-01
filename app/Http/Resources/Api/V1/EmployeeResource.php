<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Employee Resource (API V1)
 *
 * Transform employee data for API responses
 */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id_number' => $this->employee_id_number,
            'first_name' => $this->first_name,
            'second_name' => $this->second_name,
            'third_name' => $this->third_name,
            'full_name' => $this->full_name,
            'birth_date' => $this->birth_date,
            'hire_date' => $this->hire_date,
            'gender' => $this->_gender,
            'country' => $this->_country,
            'passport_number' => $this->passport_number,
            'image' => $this->image,
            'active' => (bool) $this->active,

            'admin' => $this->whenLoaded('admin', function () {
                return $this->admin ? [
                    'id' => $this->admin->id,
                    'login' => $this->admin->login,
                    'role' => $this->admin->_role,
                ] : null;
            }),

            'meta' => $this->whenLoaded('meta', function () {
                return $this->meta;
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
