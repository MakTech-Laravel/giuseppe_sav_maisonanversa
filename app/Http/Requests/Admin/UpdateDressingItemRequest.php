<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\PreparesDressingItemPayload;
use App\Models\DressingItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDressingItemRequest extends FormRequest
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
        /** @var DressingItem $dressingItem */
        $dressingItem = $this->route('dressingItem');

        return [
            ...$this->dressingItemRules(),
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new DressingItem)->getTable(), 'slug')->ignore($dressingItem->id),
            ],
        ];
    }
}
