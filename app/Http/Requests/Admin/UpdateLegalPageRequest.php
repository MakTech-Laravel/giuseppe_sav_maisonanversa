<?php

namespace App\Http\Requests\Admin;

use App\Support\Html\LegalHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLegalPageRequest extends FormRequest
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
            'body' => ['required', 'string'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('body')) {
                    return;
                }

                $sanitized = LegalHtml::sanitize((string) $this->input('body'));

                if (LegalHtml::isBlank($sanitized)) {
                    $validator->errors()->add(
                        'body',
                        __('De inhoud is leeg of ongeldig na beveiligingscontrole.'),
                    );

                    return;
                }

                $this->merge(['body' => $sanitized]);
            },
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

        $data['body'] = LegalHtml::sanitize((string) $data['body']);
        $data['is_published'] = (bool) $data['is_published'];

        return $data;
    }
}
