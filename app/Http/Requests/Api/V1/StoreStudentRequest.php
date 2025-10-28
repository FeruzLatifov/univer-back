<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Store Student Request (API V1)
 *
 * Validation for creating a new student
 */
class StoreStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can create students
        return auth()->check() && auth()->user() instanceof \App\Models\EAdmin;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\p{L}\s\-]+$/u', // Letters, spaces, hyphens (unicode)
            ],
            'second_name' => [
                'required',
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
                'required',
                'date',
                'before:today',
                'after:1940-01-01',
            ],
            'student_id_number' => [
                'required',
                'string',
                'unique:e_student,student_id_number',
                'max:50',
            ],
            '_gender' => [
                'required',
                'string',
                'exists:h_gender,code',
            ],
            '_country' => [
                'required',
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
                'unique:e_student,email',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Ism kiritish majburiy',
            'first_name.min' => 'Ism kamida 2 belgidan iborat bo\'lishi kerak',
            'first_name.regex' => 'Ism faqat harflardan iborat bo\'lishi kerak',
            'second_name.required' => 'Familiya kiritish majburiy',
            'second_name.regex' => 'Familiya faqat harflardan iborat bo\'lishi kerak',
            'birth_date.required' => 'Tug\'ilgan sana kiritish majburiy',
            'birth_date.before' => 'Tug\'ilgan sana bugungi kundan oldin bo\'lishi kerak',
            'birth_date.after' => 'Tug\'ilgan sana 1940 yildan keyin bo\'lishi kerak',
            'student_id_number.required' => 'Talaba ID raqami kiritish majburiy',
            'student_id_number.unique' => 'Bunday ID raqam allaqachon mavjud',
            '_gender.required' => 'Jins kiritish majburiy',
            '_gender.exists' => 'Noto\'g\'ri jins kodi',
            '_country.required' => 'Mamlakat kiritish majburiy',
            '_country.exists' => 'Noto\'g\'ri mamlakat kodi',
            'phone_number.regex' => 'Telefon raqami formati noto\'g\'ri',
            'email.email' => 'Email formati noto\'g\'ri',
            'email.unique' => 'Bu email allaqachon ishlatilmoqda',
            'password.required' => 'Parol kiritish majburiy',
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
                'message' => 'Faqat adminlar talaba yarata oladi',
            ], 403)
        );
    }
}
