<?php

namespace App\Http\Requests\Maison;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists((new Product)->getTable(), 'id')->where('is_published', true),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'monogram' => ['nullable', 'string', 'max:3'],
            'gift_wrap' => ['sometimes', 'boolean'],
            'gift_message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Vul a.u.b. uw naam en e-mailadres in.'),
            'email.required' => __('Vul a.u.b. uw naam en e-mailadres in.'),
        ];
    }
}
