<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslateCommunityEventColumnRequest extends FormRequest
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
            'target_locale' => ['required', 'string', Rule::in(['en', 'fr'])],
            'column' => ['required', 'string', Rule::in(['title', 'description', 'location'])],
        ];
    }
}
