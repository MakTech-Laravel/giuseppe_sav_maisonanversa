<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\PreparesDressingItemPayload;
use App\Models\DressingItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDressingItemRequest extends FormRequest
{
    use PreparesDressingItemPayload;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareDressingItemPayload();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->dressingItemRules(),
            'slug' => ['required', 'string', 'max:255', 'unique:'.(new DressingItem)->getTable().',slug'],
        ];
    }
}
