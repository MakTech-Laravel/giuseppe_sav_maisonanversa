<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Enums\ProductSectionKey;
use App\Enums\ProductStatus;
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
        // `gallery_keep` doubles as the "this request carries media" marker, so
        // it must not be synthesized when the client never sent it.
        if ($this->has('gallery_keep')) {
            $this->merge(['gallery_keep' => $this->parsedGalleryKeep()]);
        }

        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'public_at' => filled($this->input('public_at')) ? $this->input('public_at') : null,
            'grants_founding_circle' => $this->boolean('grants_founding_circle'),
            'remove_primary_image' => $this->boolean('remove_primary_image'),
            'slug' => filled($this->input('slug'))
                ? Str::slug((string) $this->input('slug'))
                : Str::slug((string) $this->input('name', '')),
            'edition_number_prefix' => $this->nullableTrimmed('edition_number_prefix'),
            'edition_number_postfix' => $this->nullableTrimmed('edition_number_postfix'),
            'eyebrow' => $this->nullableTrimmed('eyebrow'),
            'hero_eyebrow' => $this->nullableTrimmed('hero_eyebrow'),
            'hero_subtitle' => $this->nullableTrimmed('hero_subtitle'),
            'description' => $this->nullableTrimmed('description'),
            'meta_title' => $this->nullableTrimmed('meta_title'),
            'meta_description' => $this->nullableTrimmed('meta_description'),
            'meta_keywords' => $this->nullableTrimmed('meta_keywords'),
            'expected_delivery_label' => $this->nullableTrimmed('expected_delivery_label'),
            'remove_og_image' => $this->boolean('remove_og_image'),
            'archive_edition_numbers' => $this->parsedArchiveNumbers(),
            'sort_order' => (int) $this->input('sort_order', 0),
            'status' => filled($this->input('status'))
                ? (string) $this->input('status')
                : ProductStatus::Active->value,
            ...$this->normalizedProductContent(),
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
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
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
            'public_at' => ['nullable', 'date'],
            'grants_founding_circle' => ['required', 'boolean'],
            'expected_delivery_label' => ['nullable', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'hero_eyebrow' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:5000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:5000'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', File::image()->max(5 * 1024)],
            'remove_og_image' => ['sometimes', 'boolean'],
            'primary_image' => ['nullable', File::image()->max(5 * 1024)],
            'gallery_images' => ['nullable', 'array', 'max:12'],
            'gallery_images.*' => [File::image()->max(5 * 1024)],
            'remove_primary_image' => ['sometimes', 'boolean'],
            'gallery_keep' => ['nullable', 'array'],
            'gallery_keep.*' => ['string', 'max:255'],
            ...$this->productContentRules(),
        ];
    }

    /**
     * Nested section / item / FAQ rules shared by store and the partial-save
     * endpoints.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function productContentRules(): array
    {
        return [
            'sections' => ['nullable', 'array', 'max:'.count(ProductSectionKey::cases())],
            'sections.*.key' => ['required', Rule::enum(ProductSectionKey::class)],
            'sections.*.eyebrow' => ['nullable', 'string', 'max:255'],
            'sections.*.heading' => ['nullable', 'string', 'max:255'],
            'sections.*.subheading' => ['nullable', 'string', 'max:255'],
            'sections.*.intro' => ['nullable', 'string', 'max:120'],
            'sections.*.image_key' => ['nullable', 'string', 'max:255'],
            'sections.*.image' => ['nullable', 'image', 'max:10240'],
            'sections.*.remove_image' => ['sometimes', 'boolean'],
            'sections.*.is_visible' => ['required', 'boolean'],
            'sections.*.include_house_card' => ['required', 'boolean'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0', 'max:100'],
            'sections.*.items' => ['nullable', 'array', 'max:50'],
            'sections.*.items.*.number_label' => ['nullable', 'string', 'max:40'],
            'sections.*.items.*.icon' => ['nullable', 'string', 'max:80'],
            'sections.*.items.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.items.*.body' => ['nullable', 'string', 'max:5000'],
            'faqs' => ['nullable', 'array', 'max:50'],
            'faqs.*.question' => ['required', 'string', 'max:1000'],
            'faqs.*.answer' => ['required', 'string', 'max:10000'],
            'faqs.*.is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * Normalizes booleans inside the nested section / FAQ arrays, which arrive
     * as "0"/"1" strings when the form is submitted as multipart.
     *
     * @return array<string, mixed>
     */
    protected function normalizedProductContent(): array
    {
        $sections = $this->input('sections');
        $faqs = $this->input('faqs');

        $payload = [];

        if (is_array($sections)) {
            $payload['sections'] = array_values(array_map(
                function (mixed $section): array {
                    $section = is_array($section) ? $section : [];
                    $section['is_visible'] = filter_var(
                        $section['is_visible'] ?? true,
                        FILTER_VALIDATE_BOOLEAN,
                    );
                    $section['include_house_card'] = filter_var(
                        $section['include_house_card'] ?? true,
                        FILTER_VALIDATE_BOOLEAN,
                    );
                    $section['remove_image'] = filter_var(
                        $section['remove_image'] ?? false,
                        FILTER_VALIDATE_BOOLEAN,
                    );
                    $section['sort_order'] = (int) ($section['sort_order'] ?? 0);
                    $section['items'] = array_values(array_filter(
                        is_array($section['items'] ?? null) ? $section['items'] : [],
                        fn (mixed $item): bool => is_array($item)
                            && (
                                filled($item['title'] ?? null)
                                || filled($item['body'] ?? null)
                                || filled($item['icon'] ?? null)
                                || filled($item['number_label'] ?? null)
                            ),
                    ));

                    return $section;
                },
                $sections,
            ));
        }

        if (is_array($faqs)) {
            $payload['faqs'] = array_values(array_map(
                function (mixed $faq): array {
                    $faq = is_array($faq) ? $faq : [];
                    $faq['is_published'] = filter_var(
                        $faq['is_published'] ?? true,
                        FILTER_VALIDATE_BOOLEAN,
                    );

                    return $faq;
                },
                array_filter(
                    $faqs,
                    fn (mixed $faq): bool => is_array($faq)
                        && (filled($faq['question'] ?? null) || filled($faq['answer'] ?? null)),
                ),
            ));
        }

        return $payload;
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
