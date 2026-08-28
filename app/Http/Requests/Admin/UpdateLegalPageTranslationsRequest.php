<?php

namespace App\Http\Requests\Admin;

use App\Support\Html\LegalHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLegalPageTranslationsRequest extends FormRequest
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
            'nl.body' => ['required', 'string'],
            'en.body' => ['required', 'string'],
            'fr.body' => ['required', 'string'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (['nl', 'en', 'fr'] as $locale) {
                    $key = $locale.'.body';

                    if ($validator->errors()->has($key)) {
                        continue;
                    }

                    $sanitized = LegalHtml::sanitize((string) $this->input($key));

                    if (LegalHtml::isBlank($sanitized)) {
                        $validator->errors()->add(
                            $key,
                            __('De inhoud is leeg of ongeldig na beveiligingscontrole.'),
                        );

                        continue;
                    }

                    $this->merge([
                        $locale => [
                            'body' => $sanitized,
                        ],
                    ]);
                }
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

        foreach (['nl', 'en', 'fr'] as $locale) {
            $data[$locale]['body'] = LegalHtml::sanitize((string) $data[$locale]['body']);
        }

        return $data;
    }
}
