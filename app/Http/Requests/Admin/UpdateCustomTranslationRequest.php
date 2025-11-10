<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Custom Translation Update Request Validation
 */
class UpdateCustomTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Admin permission check
        return $this->user()?->hasPermission('system.translation.custom') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'language' => [
                'required',
                'string',
                'in:uz-UZ,oz-UZ,ru-RU,en-US,kk-UZ,tg-TG,kz-KZ,tm-TM,kg-KG',
            ],
            'custom_translation' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'language.required' => 'Til tanlanishi shart',
            'language.in' => 'Noto\'g\'ri til kodi',
            'custom_translation.required' => 'Tarjima matni kiritilishi shart',
            'custom_translation.string' => 'Tarjima string bo\'lishi kerak',
            'custom_translation.max' => 'Tarjima 1000 belgidan oshmasligi kerak',
        ];
    }
}
