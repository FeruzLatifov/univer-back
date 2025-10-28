<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Store Group Request (API V1)
 *
 * Validation for creating a new group
 */
class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user() instanceof \App\Models\EAdmin;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:e_group,code',
            ],
            '_department' => [
                'required',
                'integer',
                'exists:e_department,id',
            ],
            '_specialty' => [
                'required',
                'integer',
                'exists:e_specialty,id',
            ],
            '_education_type' => [
                'required',
                'string',
                'max:50',
            ],
            '_education_form' => [
                'required',
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
                'required',
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
            'name.required' => 'Guruh nomi kiritish majburiy',
            'code.required' => 'Guruh kodi kiritish majburiy',
            'code.unique' => 'Bunday kod allaqachon mavjud',
            '_department.required' => 'Fakultet tanlash majburiy',
            '_department.exists' => 'Noto\'g\'ri fakultet',
            '_specialty.required' => 'Mutaxassislik tanlash majburiy',
            '_specialty.exists' => 'Noto\'g\'ri mutaxassislik',
            '_education_type.required' => 'Ta\'lim turi tanlash majburiy',
            '_education_form.required' => 'Ta\'lim shakli tanlash majburiy',
            '_level.required' => 'Bosqich tanlash majburiy',
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
                'message' => 'Faqat adminlar guruh yarata oladi',
            ], 403)
        );
    }
}
