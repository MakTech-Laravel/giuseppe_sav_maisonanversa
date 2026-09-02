<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerClubTranslationsRequest extends FormRequest
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
            'nl.city' => ['required', 'string', 'max:255'],
            'nl.country' => ['required', 'string', 'max:255'],
            'en.city' => ['required', 'string', 'max:255'],
            'en.country' => ['required', 'string', 'max:255'],
            'fr.city' => ['required', 'string', 'max:255'],
            'fr.country' => ['required', 'string', 'max:255'],
        ];
    }
}
