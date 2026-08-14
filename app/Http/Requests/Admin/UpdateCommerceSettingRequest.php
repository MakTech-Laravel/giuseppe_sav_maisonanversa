<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommerceSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'shipping_eu_included' => $this->boolean('shipping_eu_included'),
            'prices_include_tax' => $this->boolean('prices_include_tax'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipping_estimate_min' => ['required', 'numeric', 'decimal:0,2', 'gte:0'],
            'shipping_estimate_max' => ['required', 'numeric', 'decimal:0,2', 'gte:shipping_estimate_min'],
            'shipping_eu_included' => ['required', 'boolean'],
            'default_expected_delivery_label' => ['nullable', 'string', 'max:255'],
            'prices_include_tax' => ['required', 'boolean'],
        ];
    }
}
