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
            'club_name.required' => __('Vul de naam van uw club in.'),
            'name.required' => __('Vul de contactpersoon in.'),
            'email.required' => __('Vul een geldig e-mailadres in.'),
            'email.email' => __('Vul een geldig e-mailadres in.'),
            'location.required' => __('Vul stad en land in.'),
            'courts.required' => __('Selecteer het aantal courts.'),
            'format.required' => __('Selecteer een formaat.'),
            'message.required' => __('Vertel ons over uw club.'),
            'website.prohibited' => __('Vul een geldig e-mailadres in.'),
        ];
    }
}
