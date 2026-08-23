<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunityEventTranslationsRequest extends FormRequest
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
            'en.title' => ['required', 'string', 'max:255'],
            'en.description' => ['required', 'string', 'max:5000'],
            'en.location' => ['required', 'string', 'max:255'],
            'fr.title' => ['required', 'string', 'max:255'],
            'fr.description' => ['required', 'string', 'max:5000'],
            'fr.location' => ['required', 'string', 'max:255'],
        ];
    }
}
