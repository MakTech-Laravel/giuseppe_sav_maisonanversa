<?php

namespace App\Http\Requests\Maison;

use App\Enums\SubscriberSource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsletterSubscribeRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::enum(SubscriberSource::class)],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('Vul een geldig e-mailadres in.'),
            'email.email' => __('Vul een geldig e-mailadres in.'),
            'website.prohibited' => __('Vul een geldig e-mailadres in.'),
        ];
    }
}
