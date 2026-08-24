<?php

namespace App\Http\Requests\Admin\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

trait PreparesDressingItemPayload
{
    protected function prepareDressingItemPayload(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'remove_image' => $this->boolean('remove_image'),
            'slug' => filled($this->input('slug'))
                ? Str::slug((string) $this->input('slug'))
                : Str::slug((string) $this->input('name', '')),
            'description' => $this->nullableTrimmed('description'),
            'image_key' => $this->nullableTrimmed('image_key'),
        ]);
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value !== '' ? $value : null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function dressingItemRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', 'in:coming_soon,available'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
            'image' => ['nullable', File::image()->max(5 * 1024)],
            'remove_image' => ['sometimes', 'boolean'],
            'image_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}
