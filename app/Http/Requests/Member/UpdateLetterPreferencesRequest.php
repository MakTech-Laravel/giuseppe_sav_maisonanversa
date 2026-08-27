<?php

namespace App\Http\Requests\Member;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLetterPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'heritageLetter' => $this->boolean('heritageLetter'),
            'productUpdates' => $this->boolean('productUpdates'),
            'events' => $this->boolean('events'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'heritageLetter' => ['required', 'boolean'],
            'productUpdates' => ['required', 'boolean'],
            'events' => ['required', 'boolean'],
        ];
    }
}
