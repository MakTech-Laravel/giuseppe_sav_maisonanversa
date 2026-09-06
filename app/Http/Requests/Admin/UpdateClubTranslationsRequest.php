<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClubTranslationsRequest extends FormRequest
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
        $locales = config('maison.locales');

        $rules = [];

        foreach ($locales as $locale) {
            $rules["{$locale}.corner_title"] = ['nullable', 'string', 'max:255'];
            $rules["{$locale}.corner_body"] = ['nullable', 'string'];
            $rules["{$locale}.corner_location"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
