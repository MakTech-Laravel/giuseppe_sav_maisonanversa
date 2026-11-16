<?php

namespace App\Http\Requests\Maison;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCornerInquiryRequest extends FormRequest
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
            'club_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:255'],
            'courts' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'club_name.required' => __('Please enter your club name.'),
            'name.required' => __('Please enter a contact person.'),
            'email.required' => __('Please enter a valid email address.'),
            'email.email' => __('Please enter a valid email address.'),
            'location.required' => __('Please enter city and country.'),
            'courts.required' => __('Please select the number of courts.'),
            'format.required' => __('Please select a format.'),
            'message.required' => __('Tell us about your club.'),
            'website.prohibited' => __('Please enter a valid email address.'),
        ];
    }
}
