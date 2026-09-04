<?php

namespace App\Http\Requests\Community;

use App\Enums\SessionSport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClubRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*' => [Rule::enum(SessionSport::class)],
            'street' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'website' => ['nullable', 'url', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'street' => $this->blankToNull('street'),
            'postal_code' => $this->blankToNull('postal_code'),
            'city' => $this->blankToNull('city'),
            'country' => $this->blankToNull('country'),
            'website' => $this->blankToNull('website'),
        ]);
    }

    private function blankToNull(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
