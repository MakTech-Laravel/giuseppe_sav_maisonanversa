<?php

namespace App\Http\Requests\Maison;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactInquiryRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'datum' => ['nullable', 'string', 'max:255'],
            'moment' => ['nullable', 'string', 'max:255'],
            'soort' => ['nullable', 'string', 'max:255'],
            'ervaring' => ['nullable', 'string', 'max:255'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Vul uw naam in.'),
            'email.required' => __('Vul een geldig e-mailadres in.'),
            'email.email' => __('Vul een geldig e-mailadres in.'),
            'website.prohibited' => __('Vul een geldig e-mailadres in.'),
        ];
    }
}
