<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJournalTranslationsRequest extends FormRequest
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
            'nl.excerpt' => ['required', 'string', 'max:5000'],
            'nl.body' => ['required', 'string', 'max:100000'],
            'nl.category' => ['nullable', 'string', 'max:255'],
            'nl.date_label' => ['nullable', 'string', 'max:255'],
            'en.title' => ['required', 'string', 'max:255'],
            'en.excerpt' => ['required', 'string', 'max:5000'],
            'en.body' => ['required', 'string', 'max:100000'],
            'en.category' => ['nullable', 'string', 'max:255'],
            'en.date_label' => ['nullable', 'string', 'max:255'],
            'fr.title' => ['required', 'string', 'max:255'],
            'fr.excerpt' => ['required', 'string', 'max:5000'],
            'fr.body' => ['required', 'string', 'max:100000'],
            'fr.category' => ['nullable', 'string', 'max:255'],
            'fr.date_label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
