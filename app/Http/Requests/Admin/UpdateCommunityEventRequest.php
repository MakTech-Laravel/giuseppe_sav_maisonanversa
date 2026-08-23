<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunityEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('capacity') === '' || $this->input('capacity') === 'none') {
            $this->merge(['capacity' => null]);
        }

        if ($this->input('remove_thumbnail') === '0' || $this->input('remove_thumbnail') === 'false') {
            $this->merge(['remove_thumbnail' => false]);
        }

        if ($this->input('remove_thumbnail') === '1' || $this->input('remove_thumbnail') === 'true') {
            $this->merge(['remove_thumbnail' => true]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:500'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp'],
            'remove_thumbnail' => ['sometimes', 'boolean'],
        ];
    }
}
