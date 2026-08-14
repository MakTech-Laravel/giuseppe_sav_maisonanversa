<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'grants_founding_circle' => $this->boolean('grants_founding_circle'),
            'slug' => filled($this->input('slug'))
                ? Str::slug((string) $this->input('slug'))
                : Str::slug((string) $this->input('name', '')),
            'archive_edition_numbers' => $this->parsedArchiveNumbers(),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:'.(new Product)->getTable().',slug'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'edition_total' => ['nullable', 'required_if:type,'.ProductType::LimitedEdition->value, 'integer', 'min:1'],
            'archive_edition_numbers' => ['nullable', 'array'],
            'archive_edition_numbers.*' => ['integer', 'min:1'],
            'stock_quantity' => ['nullable', 'required_if:type,'.ProductType::Simple->value, 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
            'grants_founding_circle' => ['required', 'boolean'],
            'expected_delivery_label' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return list<int>
     */
    private function parsedArchiveNumbers(): array
    {
        $value = $this->input('archive_edition_numbers');

        if (is_array($value)) {
            return array_values(array_map(intval(...), $value));
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            intval(...),
            explode(',', $value),
        ), fn (int $number): bool => $number > 0));
    }
}
