<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Translation Import Request Validation
 */
class ImportTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Admin permission check
        return $this->user()?->hasPermission('system.translation.import') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240', // 10MB
            ],
            'force' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'CSV fayl yuklanishi shart',
            'file.file' => 'Fayl noto\'g\'ri formatda',
            'file.mimes' => 'Faqat CSV formatdagi fayllar qabul qilinadi',
            'file.max' => 'Fayl hajmi 10MB dan oshmasligi kerak',
            'force.boolean' => 'Force parametri boolean bo\'lishi kerak',
        ];
    }
}
