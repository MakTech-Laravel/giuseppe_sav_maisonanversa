<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductTranslationsRequest extends FormRequest
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
            'nl.name' => ['required', 'string', 'max:255'],
            'nl.eyebrow' => ['nullable', 'string', 'max:255'],
            'nl.hero_eyebrow' => ['nullable', 'string', 'max:255'],
            'nl.hero_subtitle' => ['nullable', 'string', 'max:5000'],
            'nl.description' => ['nullable', 'string', 'max:20000'],
            'nl.expected_delivery_label' => ['nullable', 'string', 'max:255'],
            'en.name' => ['required', 'string', 'max:255'],
            'en.eyebrow' => ['nullable', 'string', 'max:255'],
            'en.hero_eyebrow' => ['nullable', 'string', 'max:255'],
            'en.hero_subtitle' => ['nullable', 'string', 'max:5000'],
            'en.description' => ['nullable', 'string', 'max:20000'],
            'en.expected_delivery_label' => ['nullable', 'string', 'max:255'],
            'fr.name' => ['required', 'string', 'max:255'],
            'fr.eyebrow' => ['nullable', 'string', 'max:255'],
            'fr.hero_eyebrow' => ['nullable', 'string', 'max:255'],
            'fr.hero_subtitle' => ['nullable', 'string', 'max:5000'],
            'fr.description' => ['nullable', 'string', 'max:20000'],
            'fr.expected_delivery_label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
