<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Update Student Password Request
 *
 * Validates password change with current password verification
 */
class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Student can change their own password
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $student = auth('student-api')->user();

                    if (!Hash::check($value, $student->password)) {
                        $fail('Joriy parol noto\'g\'ri');
                    }
                },
            ],

            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->uncompromised(),
                function ($attribute, $value, $fail) {
                    $student = auth('student-api')->user();

                    // Check if new password is same as current
                    if (Hash::check($value, $student->password)) {
                        $fail('Yangi parol joriy paroldan farq qilishi kerak');
                    }

                    // Check if password equals passport number (Yii2 validation)
                    if (isset($student->passport_number) &&
                        strtolower($value) === strtolower($student->passport_number)) {
                        $fail('Passport raqamni parol sifatida ishlatmang!');
                    }

                    // Check if password equals student ID
                    if (isset($student->student_id_number) &&
                        strtolower($value) === strtolower($student->student_id_number)) {
                        $fail('Talaba ID raqamni parol sifatida ishlatmang!');
                    }
                },
            ],

            'password_confirmation' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Joriy parolni kiriting',
            'password.required' => 'Yangi parolni kiriting',
            'password.confirmed' => 'Parol tasdiqlash mos kelmadi',
            'password.min' => 'Parol kamida :min ta belgidan iborat bo\'lishi kerak',
            'password_confirmation.required' => 'Parolni tasdiqlang',
        ];
    }

    /**
     * Get custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'Joriy parol',
            'password' => 'Yangi parol',
            'password_confirmation' => 'Parol tasdiqlash',
        ];
    }
}
