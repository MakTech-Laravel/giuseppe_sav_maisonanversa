<?php

namespace App\Http\Requests\Maison;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $editionTotal = (int) config('maison.edition.total', 100);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'edition_number' => ['required', 'integer', 'min:1', 'max:'.$editionTotal],
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
            'edition_number.required' => __('Selecteer a.u.b. een editienummer.'),
            'name.required' => __('Vul a.u.b. uw naam en e-mailadres in.'),
            'email.required' => __('Vul a.u.b. uw naam en e-mailadres in.'),
        ];
    }
}
