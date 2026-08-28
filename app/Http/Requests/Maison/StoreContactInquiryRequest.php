<?php

namespace App\Http\Requests\Maison;

use App\Enums\InquiryType;
use App\Services\Inquiry\InquirySubmissionLimiter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'kind' => ['required', Rule::enum(InquiryType::class)->only(InquiryType::contactKinds())],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
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
            'kind.required' => __('Kies een formulier.'),
            'kind.Illuminate\Validation\Rules\Enum' => __('Kies een formulier.'),
            'name.required' => __('Vul uw naam in.'),
            'email.required' => __('Vul een geldig e-mailadres in.'),
            'email.email' => __('Vul een geldig e-mailadres in.'),
            'website.prohibited' => __('Vul een geldig e-mailadres in.'),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $kind = $this->enum('kind', InquiryType::class);

                if (app(InquirySubmissionLimiter::class)->tooMany($this, $kind)) {
                    $validator->errors()->add(
                        'kind',
                        __('U kunt dit formulier maximaal twee keer per 24 uur versturen.'),
                    );
                }
            },
        ];
    }
}
