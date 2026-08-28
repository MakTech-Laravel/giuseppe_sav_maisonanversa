<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nl.announcement_text' => ['nullable', 'string', 'max:255'],
            'en.announcement_text' => ['nullable', 'string', 'max:255'],
            'fr.announcement_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
