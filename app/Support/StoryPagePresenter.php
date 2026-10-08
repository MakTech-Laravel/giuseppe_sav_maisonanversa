<?php

namespace App\Support;

use App\Models\Product;
use App\Models\StoryPage;
use Illuminate\Support\Facades\Storage;

class StoryPagePresenter
{
    /**
     * @return array{
     *     hero: array{eyebrow: string, title: string, titleAccent: string, body: string, imageUrl: string|null}|null,
     *     city: array{eyebrow: string, title: string, bodyOne: string, bodyTwo: string, closer: string, imageUrl: string|null}|null,
     *     ritual: array{eyebrow: string, title: string, steps: list<array{number: string, title: string, body: string}>, footer: string}|null,
     *     origins: array{eyebrow: string, lead: string, title: string, body: string, italic: string, close: string}|null,
     *     name: array{eyebrow: string, title: string, pronunciation: string, body: string, imageUrl: string|null}|null,
     *     make: array{eyebrow: string, title: string, bodyOne: string, bodyTwo: string, buttonLabel: string, buttonHref: string, captions: list<string>, imageUrls: list<string|null>}|null,
     *     quote: array{line: string}|null,
     *     founder: array{eyebrow: string, name: string, role: string, paragraphs: list<string>, signature: string, imageUrl: string|null}|null,
     *     closing: array{line: string, place: string}|null
     * }
     */
    public function toStorefront(StoryPage $page, string $locale): array
    {
        return [
            'hero' => $page->hero_visible ? [
                'eyebrow' => $page->copy('hero_eyebrow', $locale),
                'title' => $page->copy('hero_title', $locale),
                'titleAccent' => $page->copy('hero_title_accent', $locale),
                'body' => $page->copy('hero_body', $locale),
                'imageUrl' => $this->imageUrl($page->hero_image_path),
            ] : null,
            'city' => $page->city_visible ? [
                'eyebrow' => $page->copy('city_eyebrow', $locale),
                'title' => $page->copy('city_title', $locale),
                'bodyOne' => $page->copy('city_body_one', $locale),
                'bodyTwo' => $page->copy('city_body_two', $locale),
                'closer' => $page->copy('city_closer', $locale),
                'imageUrl' => $this->imageUrl($page->city_image_path),
            ] : null,
            'ritual' => $page->ritual_visible ? [
                'eyebrow' => $page->copy('ritual_eyebrow', $locale),
                'title' => $page->copy('ritual_title', $locale),
                'steps' => [
                    ['number' => '01', 'title' => $page->copy('ritual_step_1_title', $locale), 'body' => $page->copy('ritual_step_1_body', $locale)],
                    ['number' => '02', 'title' => $page->copy('ritual_step_2_title', $locale), 'body' => $page->copy('ritual_step_2_body', $locale)],
                    ['number' => '03', 'title' => $page->copy('ritual_step_3_title', $locale), 'body' => $page->copy('ritual_step_3_body', $locale)],
                    ['number' => '04', 'title' => $page->copy('ritual_step_4_title', $locale), 'body' => $page->copy('ritual_step_4_body', $locale)],
                ],
                'footer' => $page->copy('ritual_footer', $locale),
            ] : null,
            'origins' => $page->origins_visible ? [
                'eyebrow' => $page->copy('origins_eyebrow', $locale),
                'lead' => $page->copy('origins_lead', $locale),
                'title' => $page->copy('origins_title', $locale),
                'body' => $page->copy('origins_body', $locale),
                'italic' => $page->copy('origins_italic', $locale),
                'close' => $page->copy('origins_close', $locale),
            ] : null,
            'name' => $page->name_visible ? [
                'eyebrow' => $page->copy('name_eyebrow', $locale),
                'title' => $page->copy('name_title', $locale),
                'pronunciation' => $page->copy('name_pronunciation', $locale),
                'body' => $page->copy('name_body', $locale),
                'imageUrl' => $this->imageUrl($page->name_image_path),
            ] : null,
            'make' => $page->make_visible ? [
                'eyebrow' => $page->copy('make_eyebrow', $locale),
                'title' => $page->copy('make_title', $locale),
                'bodyOne' => $page->copy('make_body_one', $locale),
                'bodyTwo' => $page->copy('make_body_two', $locale),
                'buttonLabel' => $page->copy('make_button_label', $locale),
                'buttonHref' => route('maison.products.show', [
                    'locale' => $locale,
                    'product' => Product::FOUNDING_SLUG,
                ]),
                'captions' => [
                    $page->copy('make_caption_one', $locale),
                    $page->copy('make_caption_two', $locale),
                    $page->copy('make_caption_three', $locale),
                ],
                'imageUrls' => [
                    $this->imageUrl($page->make_image_one_path),
                    $this->imageUrl($page->make_image_two_path),
                    $this->imageUrl($page->make_image_three_path),
                ],
            ] : null,
            'quote' => $page->quote_visible ? [
                'line' => $page->copy('quote_line', $locale),
            ] : null,
            'founder' => $page->founder_visible ? [
                'eyebrow' => $page->copy('founder_eyebrow', $locale),
                'name' => $page->copy('founder_name', $locale),
                'role' => $page->copy('founder_role', $locale),
                'paragraphs' => [
                    $page->copy('founder_paragraph_one', $locale),
                    $page->copy('founder_paragraph_two', $locale),
                    $page->copy('founder_paragraph_three', $locale),
                    $page->copy('founder_paragraph_four', $locale),
                    $page->copy('founder_paragraph_five', $locale),
                    $page->copy('founder_paragraph_six', $locale),
                ],
                'signature' => $page->copy('founder_signature', $locale),
                'imageUrl' => $this->imageUrl($page->founder_image_path),
            ] : null,
            'closing' => $page->closing_visible ? [
                'line' => $page->copy('closing_line', $locale),
                'place' => $page->copy('closing_place', $locale),
            ] : null,
        ];
    }

    private function imageUrl(mixed $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
