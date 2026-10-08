<?php

namespace App\Http\Requests\Admin;

use App\Models\StoryPage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStoryPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $flags = [];

        foreach (StoryPage::VISIBLE_COLUMNS as $column) {
            $flags[$column] = $this->boolean($column);
        }

        foreach (array_keys(StoryPage::IMAGE_SLOTS) as $slot) {
            $flags["{$slot}_remove_image"] = $this->boolean("{$slot}_remove_image");
        }

        $this->merge($flags);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (StoryPage::VISIBLE_COLUMNS as $column) {
            $rules[$column] = ['required', 'boolean'];
        }

        foreach ([...StoryPage::UNTRANSLATED_COLUMNS, ...StoryPage::TRANSLATION_COLUMNS] as $column) {
            $rules[$column] = ['required', 'string', 'max:'.StoryPage::maxLength($column)];
        }

        foreach (array_keys(StoryPage::IMAGE_SLOTS) as $slot) {
            $rules["{$slot}_image"] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'];
            $rules["{$slot}_remove_image"] = ['sometimes', 'boolean'];
        }

        return $rules;
    }
}
