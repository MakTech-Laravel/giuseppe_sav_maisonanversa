<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\PreparesProductPayload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductSectionsRequest extends FormRequest
{
    use PreparesProductPayload;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normalizedProductContent());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->productContentRules();

        unset($rules['faqs'], $rules['faqs.*.question'], $rules['faqs.*.answer'], $rules['faqs.*.is_published']);

        $rules['sections'] = ['required', 'array'];

        return $rules;
    }
}
