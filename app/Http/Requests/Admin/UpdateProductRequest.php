<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\PreparesProductPayload;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    use PreparesProductPayload;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareProductPayload();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            ...$this->productRules(),
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new Product)->getTable(), 'slug')->ignore($product->id),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->withProductValidator($validator);
    }
}
