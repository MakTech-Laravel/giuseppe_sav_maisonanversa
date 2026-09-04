<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDressingItemTranslationsRequest extends FormRequest
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
            'nl.category' => ['required', 'string', 'max:255'],
            'nl.description' => ['nullable', 'string', 'max:20000'],
            'en.name' => ['required', 'string', 'max:255'],
            'en.category' => ['required', 'string', 'max:255'],
            'en.description' => ['nullable', 'string', 'max:20000'],
            'fr.name' => ['required', 'string', 'max:255'],
            'fr.category' => ['required', 'string', 'max:255'],
            'fr.description' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
