<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateProductMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $galleryKeep = $this->input('gallery_keep');

        $this->merge([
            'remove_primary_image' => $this->boolean('remove_primary_image'),
            'gallery_keep' => is_array($galleryKeep)
                ? array_values(array_filter(
                    array_map(fn (mixed $path): string => is_string($path) ? $path : '', $galleryKeep),
                    fn (string $path): bool => $path !== '',
                ))
                : [],
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'primary_image' => ['nullable', File::image()->max(5 * 1024)],
            'gallery_images' => ['nullable', 'array', 'max:12'],
            'gallery_images.*' => [File::image()->max(5 * 1024)],
            'remove_primary_image' => ['sometimes', 'boolean'],
            'gallery_keep' => ['nullable', 'array'],
            'gallery_keep.*' => ['string', 'max:255'],
        ];
    }
}
