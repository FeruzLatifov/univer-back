<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Update Student Request (API V1)
 *
 * Validation for updating a student
 */
class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can update students
        return auth()->check() && auth()->user() instanceof \App\Models\EAdmin;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $studentId = $this->route('student'); // Get student ID from route

        return [
            'first_name' => [
                'sometimes',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\p{L}\s\-]+$/u',
            ],
            'second_name' => [
                'sometimes',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\p{L}\s\-]+$/u',
            ],
            'third_name' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[\p{L}\s\-]+$/u',
            ],
            'birth_date' => [
                'sometimes',
                'date',
                'before:today',
                'after:1940-01-01',
            ],
            'student_id_number' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('e_student', 'student_id_number')->ignore($studentId),
            ],
            '_gender' => [
                'sometimes',
                'string',
                'exists:h_gender,code',
            ],
            '_country' => [
                'sometimes',
                'string',
                'exists:h_country,code',
            ],
            'passport_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'passport_pin' => [
                'nullable',
                'string',
                'max:50',
            ],
            'phone_number' => [
                'nullable',
                'string',
                'regex:/^\+?[0-9]{9,15}$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('e_student', 'email')->ignore($studentId),
            ],
            'password' => [
                'sometimes',
                'string',
                'min:6',
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'first_name.min' => 'Ism kamida 2 belgidan iborat bo\'lishi kerak',
            'first_name.regex' => 'Ism faqat harflardan iborat bo\'lishi kerak',
            'second_name.regex' => 'Familiya faqat harflardan iborat bo\'lishi kerak',
            'birth_date.before' => 'Tug\'ilgan sana bugungi kundan oldin bo\'lishi kerak',
            'birth_date.after' => 'Tug\'ilgan sana 1940 yildan keyin bo\'lishi kerak',
            'student_id_number.unique' => 'Bunday ID raqam allaqachon mavjud',
            '_gender.exists' => 'Noto\'g\'ri jins kodi',
            '_country.exists' => 'Noto\'g\'ri mamlakat kodi',
            'phone_number.regex' => 'Telefon raqami formati noto\'g\'ri',
            'email.email' => 'Email formati noto\'g\'ri',
            'email.unique' => 'Bu email allaqachon ishlatilmoqda',
            'password.min' => 'Parol kamida 6 belgidan iborat bo\'lishi kerak',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
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

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Faqat adminlar talaba ma\'lumotlarini o\'zgartira oladi',
            ], 403)
        );
    }
}
