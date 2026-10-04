<?php

namespace App\Http\Requests\Admin;

use App\Models\HomeHero;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHomeHeroTranslationsRequest extends FormRequest
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
        $rules = [];

        foreach (config('maison.locales') as $locale) {
            foreach (HomeHero::TRANSLATION_COLUMNS as $column) {
                $rules["{$locale}.{$column}"] = ['required', 'string', 'max:'.$this->maxLength($column)];
            }
        }

        return $rules;
    }

    private function maxLength(string $column): int
    {
        return match ($column) {
            'eyebrow' => 160,
            'tagline' => 255,
            default => 80,
        };
    }
}
