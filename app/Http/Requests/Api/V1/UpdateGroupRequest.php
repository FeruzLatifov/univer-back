<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Update Group Request (API V1)
 *
 * Validation for updating a group
 */
class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user() instanceof \App\Models\EAdmin;
    }

    public function rules(): array
    {
        $groupId = $this->route('group');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('e_group', 'code')->ignore($groupId),
            ],
            '_department' => [
                'sometimes',
                'integer',
                'exists:e_department,id',
            ],
            '_specialty' => [
                'sometimes',
                'integer',
                'exists:e_specialty,id',
            ],
            '_education_type' => [
                'sometimes',
                'string',
                'max:50',
            ],
            '_education_form' => [
                'sometimes',
                'string',
                'max:50',
            ],
            '_education_year' => [
                'nullable',
                'integer',
                'min:1',
                'max:7',
            ],
            '_level' => [
                'sometimes',
                'string',
                'max:50',
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Bunday kod allaqachon mavjud',
            '_department.exists' => 'Noto\'g\'ri fakultet',
            '_specialty.exists' => 'Noto\'g\'ri mutaxassislik',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Faqat adminlar guruh ma\'lumotlarini o\'zgartira oladi',
            ], 403)
        );
    }
}
