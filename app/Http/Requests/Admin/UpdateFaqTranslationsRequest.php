<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFaqTranslationsRequest extends FormRequest
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
            'en.question' => ['required', 'string', 'max:5000'],
            'en.answer' => ['required', 'string', 'max:20000'],
            'fr.question' => ['required', 'string', 'max:5000'],
            'fr.answer' => ['required', 'string', 'max:20000'],
        ];
    }
}
