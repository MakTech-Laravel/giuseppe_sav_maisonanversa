<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductSectionTranslationsRequest extends FormRequest
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
            'nl' => ['required', 'array'],
            'en' => ['required', 'array'],
            'fr' => ['required', 'array'],
            'nl.sections' => ['nullable', 'array'],
            'en.sections' => ['nullable', 'array'],
            'fr.sections' => ['nullable', 'array'],
            '*.sections.*.eyebrow' => ['nullable', 'string', 'max:255'],
            '*.sections.*.heading' => ['nullable', 'string', 'max:255'],
            '*.sections.*.subheading' => ['nullable', 'string', 'max:255'],
            '*.sections.*.intro' => ['nullable', 'string', 'max:5000'],
            '*.sections.*.items' => ['nullable', 'array'],
            '*.sections.*.items.*.title' => ['nullable', 'string', 'max:255'],
            '*.sections.*.items.*.body' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
