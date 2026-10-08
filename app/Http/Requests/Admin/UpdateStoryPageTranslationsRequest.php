<?php

namespace App\Http\Requests\Admin;

use App\Models\StoryPage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoryPageTranslationsRequest extends FormRequest
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
            foreach (StoryPage::TRANSLATION_COLUMNS as $column) {
                $rules["{$locale}.{$column}"] = ['required', 'string', 'max:'.StoryPage::maxLength($column)];
            }
        }

        return $rules;
    }
}
