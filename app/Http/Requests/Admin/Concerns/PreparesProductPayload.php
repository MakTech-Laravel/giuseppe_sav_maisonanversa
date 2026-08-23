<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\ProductType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

trait PreparesProductPayload
{
    protected function prepareProductPayload(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'grants_founding_circle' => $this->boolean('grants_founding_circle'),
            'remove_primary_image' => $this->boolean('remove_primary_image'),
            'slug' => filled($this->input('slug'))
                ? Str::slug((string) $this->input('slug'))
                : Str::slug((string) $this->input('name', '')),
            'edition_number_prefix' => $this->nullableTrimmed('edition_number_prefix'),
            'edition_number_postfix' => $this->nullableTrimmed('edition_number_postfix'),
            'archive_edition_numbers' => $this->parsedArchiveNumbers(),
            'gallery_keep' => $this->parsedGalleryKeep(),
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
    protected function productRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'edition_total' => [
                'nullable',
                'required_if:type,'.ProductType::LimitedEdition->value,
                'integer',
                'min:1',
                'max:10000',
            ],
            'edition_number_prefix' => ['nullable', 'string', 'max:40'],
            'edition_number_postfix' => ['nullable', 'string', 'max:40'],
            'archive_edition_numbers' => ['nullable', 'array'],
            'archive_edition_numbers.*' => ['integer', 'min:1'],
            'stock_quantity' => [
                'nullable',
                'required_if:type,'.ProductType::Simple->value,
                'integer',
                'min:0',
            ],
            'is_published' => ['required', 'boolean'],
            'grants_founding_circle' => ['required', 'boolean'],
            'expected_delivery_label' => ['nullable', 'string', 'max:255'],
            'primary_image' => ['nullable', File::image()->max(5 * 1024)],
            'gallery_images' => ['nullable', 'array', 'max:12'],
            'gallery_images.*' => [File::image()->max(5 * 1024)],
            'remove_primary_image' => ['sometimes', 'boolean'],
            'gallery_keep' => ['nullable', 'array'],
            'gallery_keep.*' => ['string', 'max:255'],
        ];
    }

    protected function withProductValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') !== ProductType::LimitedEdition->value) {
                return;
            }

            $editionTotal = (int) $this->input('edition_total', 0);
            $archives = $this->input('archive_edition_numbers', []);

            if (! is_array($archives) || $editionTotal < 1) {
                return;
            }

            foreach ($archives as $index => $number) {
                if ((int) $number > $editionTotal) {
                    $validator->errors()->add(
                        "archive_edition_numbers.{$index}",
                        __('Archiefnummers moeten binnen de editiegrootte vallen.'),
                    );
                }
            }
        });
    }

    /**
     * @return list<int>
     */
    private function parsedArchiveNumbers(): array
    {
        $value = $this->input('archive_edition_numbers');

        if (is_array($value)) {
            return array_values(array_unique(array_filter(
                array_map(intval(...), $value),
                fn (int $number): bool => $number > 0,
            )));
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(intval(...), explode(',', $value)),
            fn (int $number): bool => $number > 0,
        )));
    }

    /**
     * @return list<string>
     */
    private function parsedGalleryKeep(): array
    {
        $value = $this->input('gallery_keep');

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                fn (mixed $path): string => is_string($path) ? $path : '',
                $value,
            ),
            fn (string $path): bool => $path !== '',
        ));
    }
}
