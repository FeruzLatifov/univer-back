<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Student Profile Request
 *
 * Validates student profile update data (phone, email, contacts)
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Student can update their own profile
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get authenticated student
        $studentId = auth('student-api')->id();

        return [
            // Contact Information
            'phone' => [
                'nullable',
                'string',
                'regex:/^[\+\(]{0,2}[998]{0,3}[\)]{0,1}[ ]{0,1}[0-9]{2}[- ]{0,1}[0-9]{3}[- ]{0,1}[0-9]{2}[- ]{0,1}[0-9]{2}$/',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            // Current Address (optional)
            'current_address' => [
                'nullable',
                'string',
                'max:500',
            ],

            // Additional contact info
            'telegram_username' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^@?[a-zA-Z0-9_]{5,32}$/',
            ],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Telefon raqam formati noto\'g\'ri. Misol: +998 90 123-45-67',
            'email.email' => 'Email manzil formati noto\'g\'ri',
            'email.max' => 'Email manzil juda uzun',
            'telegram_username.regex' => 'Telegram username formati noto\'g\'ri. Misol: @username',
            'current_address.max' => 'Manzil juda uzun (maksimal 500 belgi)',
        ];
    }

    /**
     * Get custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'phone' => 'Telefon raqam',
            'email' => 'Email',
            'current_address' => 'Joriy manzil',
            'telegram_username' => 'Telegram',
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Clean up phone number (remove spaces, dashes)
        if ($this->filled('phone')) {
            $phone = preg_replace('/[^0-9+]/', '', $this->phone);
            $this->merge(['phone' => $phone]);
        }

        // Clean up telegram username (remove @)
        if ($this->filled('telegram_username')) {
            $username = ltrim($this->telegram_username, '@');
            $this->merge(['telegram_username' => '@' . $username]);
        }
    }
}
