<?php

namespace App\Models;

use App\Models\Concerns\TranslatesWithDeepL;
use Illuminate\Database\Eloquent\Model;

class StoryPage extends Model
{
    use TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    public const VISIBLE_COLUMNS = [
        'hero_visible',
        'city_visible',
        'ritual_visible',
        'origins_visible',
        'name_visible',
        'make_visible',
        'quote_visible',
        'founder_visible',
        'closing_visible',
    ];

    /**
     * Proper nouns and the pronunciation stay on the Dutch row and are never sent to DeepL.
     *
     * @var list<string>
     */
    public const UNTRANSLATED_COLUMNS = [
        'name_title',
        'name_pronunciation',
        'make_title',
        'founder_name',
        'founder_signature',
    ];

    /**
     * Uploaded photographs. Empty means the storefront keeps its current brand image.
     *
     * @var list<string>
     */
    public const IMAGE_COLUMNS = [
        'hero_image_path',
        'city_image_path',
        'name_image_path',
        'make_image_one_path',
        'make_image_two_path',
        'make_image_three_path',
        'founder_image_path',
    ];

    /**
     * Form slot name to the column that stores the upload.
     *
     * @var array<string, string>
     */
    public const IMAGE_SLOTS = [
        'hero' => 'hero_image_path',
        'city' => 'city_image_path',
        'name' => 'name_image_path',
        'make_one' => 'make_image_one_path',
        'make_two' => 'make_image_two_path',
        'make_three' => 'make_image_three_path',
        'founder' => 'founder_image_path',
    ];

    /**
     * Dutch source columns DeepL may translate.
     *
     * @var list<string>
     */
    public const TRANSLATION_COLUMNS = [
        'hero_eyebrow',
        'hero_title',
        'hero_title_accent',
        'hero_body',
        'city_eyebrow',
        'city_title',
        'city_body_one',
        'city_body_two',
        'city_closer',
        'ritual_eyebrow',
        'ritual_title',
        'ritual_step_1_title',
        'ritual_step_1_body',
        'ritual_step_2_title',
        'ritual_step_2_body',
        'ritual_step_3_title',
        'ritual_step_3_body',
        'ritual_step_4_title',
        'ritual_step_4_body',
        'ritual_footer',
        'origins_eyebrow',
        'origins_lead',
        'origins_title',
        'origins_body',
        'origins_italic',
        'origins_close',
        'name_eyebrow',
        'name_body',
        'make_eyebrow',
        'make_body_one',
        'make_body_two',
        'make_button_label',
        'make_caption_one',
        'make_caption_two',
        'make_caption_three',
        'quote_line',
        'founder_eyebrow',
        'founder_role',
        'founder_paragraph_one',
        'founder_paragraph_two',
        'founder_paragraph_three',
        'founder_paragraph_four',
        'founder_paragraph_five',
        'founder_paragraph_six',
        'closing_line',
        'closing_place',
    ];

    /**
     * @var list<string>
     */
    protected array $translatable = self::TRANSLATION_COLUMNS;

    /**
     * @var list<string>
     */
    protected array $translationExcept = [
        ...self::VISIBLE_COLUMNS,
        ...self::UNTRANSLATED_COLUMNS,
        ...self::IMAGE_COLUMNS,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        ...self::VISIBLE_COLUMNS,
        ...self::UNTRANSLATED_COLUMNS,
        ...self::TRANSLATION_COLUMNS,
        ...self::IMAGE_COLUMNS,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_fill_keys(self::VISIBLE_COLUMNS, 'boolean');
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing !== null) {
            return $existing;
        }

        return static::query()->create(static::defaults());
    }

    /**
     * Today's Dutch story copy. English and French come from DeepL.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'hero_visible' => true,
            'hero_eyebrow' => 'Ons verhaal',
            'hero_title' => 'Everything begins with',
            'hero_title_accent' => 'een koffie.',
            'hero_body' => 'Coffee. Style. The city. And then the court.',
            'city_visible' => true,
            'city_eyebrow' => '01 · The city',
            'city_title' => 'Antwerpen',
            'city_body_one' => 'Every day here begins the same way. A coffee while the city slowly wakes up. The first tram passes by, shops open, terraces are set out.',
            'city_body_two' => 'Antwerp has always looked outward. A port city where trade, culture, fashion and ideas meet. Historic without standing still. European, but with a character of its own.',
            'city_closer' => 'That is where Maison Anversa begins.',
            'ritual_visible' => true,
            'ritual_eyebrow' => '02 · The ritual',
            'ritual_title' => 'Style doesn\'t stop where sport begins.',
            'ritual_step_1_title' => 'Aankleden',
            'ritual_step_1_body' => 'With care, even on an ordinary morning.',
            'ritual_step_2_title' => 'Koffie',
            'ritual_step_2_body' => 'On a terrace, while the city wakes up.',
            'ritual_step_3_title' => 'De stad',
            'ritual_step_3_body' => 'A short walk. Looking, listening, tasting.',
            'ritual_step_4_title' => 'De baan',
            'ritual_step_4_body' => 'Sportswear on. And play.',
            'ritual_footer' => 'For us, these moments belong together. The coffee before, the conversation after, and everything you wear, from the terrace to the court.',
            'origins_visible' => true,
            'origins_eyebrow' => '03 · How it began',
            'origins_lead' => 'Maison Anversa did not grow out of a business plan, but out of things I have loved for years.',
            'origins_title' => 'Coffee. Fashion. History. Sport.',
            'origins_body' => 'For a long time, they stood apart. Until I discovered padel and began to wonder why the world around sport often feels so different from the world beyond it.',
            'origins_italic' => 'Why should style stop when you step onto the court?',
            'origins_close' => 'That is how the idea for Maison Anversa was born: one house where sport, style, culture and craftsmanship come together.',
            'name_visible' => true,
            'name_eyebrow' => '04 · The name',
            'name_title' => 'Anversa',
            'name_pronunciation' => '/anˈvɛrsa/',
            'name_body' => 'Anversa is the Italian name for Antwerp. We chose it for a city that has always looked beyond its borders, and for a house with roots in Antwerp and its eyes on Europe.',
            'make_visible' => true,
            'make_eyebrow' => '05 · What we make',
            'make_title' => 'Heritage Nº 001',
            'make_body_one' => 'Our first chapter: a padel racket in an edition of one hundred. Each piece receives its own number, a certificate, a passport and a letter. Not because it has to, but because a first product deserves a first chapter.',
            'make_body_two' => 'The first hundred pieces together form the Founding Circle — numbered from 001 to 100 and recorded in the register of the House.',
            'make_button_label' => 'Ontdek Heritage Nº 001',
            'make_caption_one' => 'The certificate',
            'make_caption_two' => 'The passport',
            'make_caption_three' => 'De brief',
            'quote_visible' => true,
            'quote_line' => 'Padel is our beginning. Not our end.',
            'founder_visible' => true,
            'founder_eyebrow' => '06 · The founder',
            'founder_name' => 'Yusuf Savran',
            'founder_role' => 'Founder, Maison Anversa',
            'founder_paragraph_one' => 'I have always loved things that are made with care. Clothes that last for years. Old buildings and the stories behind them. Coffee, cities and, of course, sport.',
            'founder_paragraph_two' => 'When I started playing padel, I noticed something. The world I found beautiful off the court, I hardly found on the court.',
            'founder_paragraph_three' => 'That is where Maison Anversa began.',
            'founder_paragraph_four' => 'Not with the idea of simply making a racket, but of building a house around sport, step by step — with attention to design, materials, culture and the moments around the game.',
            'founder_paragraph_five' => 'We begin with padel. With one hundred rackets.',
            'founder_paragraph_six' => 'Where it ends, I don\'t know yet. And perhaps that is exactly what makes it beautiful.',
            'founder_signature' => 'Yusuf',
            'closing_visible' => true,
            'closing_line' => 'Every legacy begins with a first chapter.',
            'closing_place' => 'Antwerp, Belgium · Est. 2026',
            'hero_image_path' => null,
            'city_image_path' => null,
            'name_image_path' => null,
            'make_image_one_path' => null,
            'make_image_two_path' => null,
            'make_image_three_path' => null,
            'founder_image_path' => null,
        ];
    }

    public static function maxLength(string $column): int
    {
        if (in_array($column, [
            'hero_body',
            'city_body_one',
            'city_body_two',
            'city_closer',
            'ritual_footer',
            'origins_lead',
            'origins_body',
            'origins_italic',
            'origins_close',
            'name_body',
            'make_body_one',
            'make_body_two',
            'quote_line',
            'founder_paragraph_one',
            'founder_paragraph_two',
            'founder_paragraph_three',
            'founder_paragraph_four',
            'founder_paragraph_five',
            'founder_paragraph_six',
            'closing_line',
        ], true)) {
            return 2000;
        }

        return 160;
    }

    public function copy(string $column, string $locale): string
    {
        if (in_array($column, self::UNTRANSLATED_COLUMNS, true)) {
            return (string) $this->getAttribute($column);
        }

        return $this->translated($column, $locale);
    }
}
