<?php

namespace App\Http\Requests\Admin;

use App\Enums\FaqContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFaqRequest extends FormRequest
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
            'context' => ['required', Rule::enum(FaqContext::class)],
            'question' => ['required', 'string', 'max:5000'],
            'answer' => ['required', 'string', 'max:20000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_published'] = (bool) $data['is_published'];

        return $data;
    }
}
