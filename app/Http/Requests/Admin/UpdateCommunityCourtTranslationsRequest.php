<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunityCourtTranslationsRequest extends FormRequest
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
            'nl.title' => ['required', 'string', 'max:255'],
            'nl.body' => ['nullable', 'string', 'max:10000'],
            'nl.location' => ['nullable', 'string', 'max:255'],
            'en.title' => ['required', 'string', 'max:255'],
            'en.body' => ['nullable', 'string', 'max:10000'],
            'en.location' => ['nullable', 'string', 'max:255'],
            'fr.title' => ['required', 'string', 'max:255'],
            'fr.body' => ['nullable', 'string', 'max:10000'],
            'fr.location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
