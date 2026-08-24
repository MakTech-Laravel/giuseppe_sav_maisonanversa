<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunityPostTranslationsRequest extends FormRequest
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
            'nl.content' => ['required', 'string', 'max:2000'],
            'en.content' => ['required', 'string', 'max:2000'],
            'fr.content' => ['required', 'string', 'max:2000'],
        ];
    }
}
