<?php

namespace App\Http\Requests\Member;

use App\Enums\RegisterVisibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegisterListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFoundingCircle() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'visibility' => ['required', Rule::enum(RegisterVisibility::class)],
            'consent' => ['nullable', 'boolean'],
        ];
    }
}
