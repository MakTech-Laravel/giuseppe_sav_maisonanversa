<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHeritageProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'edition_number_prefix' => $this->nullableTrimmed('edition_number_prefix'),
            'edition_number_postfix' => $this->nullableTrimmed('edition_number_postfix'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'edition_number_prefix' => ['nullable', 'string', 'max:40'],
            'edition_number_postfix' => ['nullable', 'string', 'max:40'],
        ];
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }
}
