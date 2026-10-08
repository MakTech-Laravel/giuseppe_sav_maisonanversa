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
            'hero_title' => 'Alles begint bij',
            'hero_title_accent' => 'een koffie.',
            'hero_body' => 'Koffie. Stijl. De stad. En dan de baan.',
            'city_visible' => true,
            'city_eyebrow' => '01 · De stad',
            'city_title' => 'Antwerpen',
            'city_body_one' => 'Elke dag begint hier op dezelfde manier. Een koffie terwijl de stad langzaam wakker wordt. De eerste tram rijdt voorbij, winkels gaan open, terrassen worden klaargezet.',
            'city_body_two' => 'Antwerpen heeft altijd naar buiten gekeken. Een havenstad waar handel, cultuur, mode en ideeën samenkomen. Historisch zonder stil te staan. Europees, maar met een eigen karakter.',
            'city_closer' => 'Daar begint Maison Anversa.',
            'ritual_visible' => true,
            'ritual_eyebrow' => '02 · Het ritueel',
            'ritual_title' => 'Stijl stopt niet waar sport begint.',
            'ritual_step_1_title' => 'Aankleden',
            'ritual_step_1_body' => 'Met zorg, ook op een gewone ochtend.',
            'ritual_step_2_title' => 'Koffie',
            'ritual_step_2_body' => 'Op een terras, terwijl de stad wakker wordt.',
            'ritual_step_3_title' => 'De stad',
            'ritual_step_3_body' => 'Even rondlopen. Kijken, luisteren, proeven.',
            'ritual_step_4_title' => 'De baan',
            'ritual_step_4_body' => 'Sportkleren aan. En spelen.',
            'ritual_footer' => 'Voor ons horen die momenten bij elkaar. De koffie ervoor, het gesprek erna, en alles wat je draagt, van het terras tot op de baan.',
            'origins_visible' => true,
            'origins_eyebrow' => '03 · Hoe het begon',
            'origins_lead' => 'Maison Anversa ontstond niet uit een businessplan, maar uit dingen waar ik al jaren van hou.',
            'origins_title' => 'Koffie. Mode. Geschiedenis. Sport.',
            'origins_body' => 'Lange tijd stonden ze los van elkaar. Tot ik padel ontdekte en me begon af te vragen waarom de wereld rondom sport vaak zo anders voelt dan de wereld daarbuiten.',
            'origins_italic' => 'Waarom zou stijl stoppen wanneer je de baan opstapt?',
            'origins_close' => 'Zo ontstond het idee voor Maison Anversa: één huis waarin sport, stijl, cultuur en vakmanschap samenkomen.',
            'name_visible' => true,
            'name_eyebrow' => '04 · De naam',
            'name_title' => 'Anversa',
            'name_pronunciation' => '/anˈvɛrsa/',
            'name_body' => 'Anversa is de Italiaanse naam voor Antwerpen. We kozen hem voor een stad die altijd over haar grenzen heen keek, en voor een huis met wortels in Antwerpen en een blik op Europa.',
            'make_visible' => true,
            'make_eyebrow' => '05 · Wat we maken',
            'make_title' => 'Heritage No.001',
            'make_body_one' => 'Ons eerste hoofdstuk: een padelracket in een oplage van honderd stuks. Elk exemplaar krijgt een eigen nummer, een certificaat, een paspoort en een brief. Niet omdat het moet, maar omdat een eerste product een eerste hoofdstuk verdient.',
            'make_body_two' => 'De eerste honderd exemplaren vormen samen de Founding Circle — genummerd van 001 tot 100 en vastgelegd in het register van het huis.',
            'make_button_label' => 'Ontdek Heritage No.001',
            'make_caption_one' => 'Het certificaat',
            'make_caption_two' => 'Het paspoort',
            'make_caption_three' => 'De brief',
            'quote_visible' => true,
            'quote_line' => 'Padel is ons begin. Niet ons einde.',
            'founder_visible' => true,
            'founder_eyebrow' => '06 · De oprichter',
            'founder_name' => 'Yusuf Savran',
            'founder_role' => 'Oprichter, Maison Anversa',
            'founder_paragraph_one' => 'Ik heb altijd gehouden van dingen die met aandacht gemaakt worden. Van kleding die jaren meegaat. Van oude gebouwen en de verhalen erachter. Van koffie, steden en natuurlijk sport.',
            'founder_paragraph_two' => 'Toen ik padel begon te spelen, merkte ik iets op. De wereld die ik mooi vond buiten de baan, vond ik nauwelijks terug op de baan.',
            'founder_paragraph_three' => 'Daar ontstond Maison Anversa.',
            'founder_paragraph_four' => 'Niet met het idee om zomaar een racket te maken, maar om stap voor stap een huis rond sport te bouwen — met aandacht voor design, materiaal, cultuur en de momenten rondom het spel.',
            'founder_paragraph_five' => 'We beginnen met padel. Met honderd rackets.',
            'founder_paragraph_six' => 'Waar het eindigt, weet ik nog niet. En misschien is dat juist het mooie eraan.',
            'founder_signature' => 'Yusuf',
            'closing_visible' => true,
            'closing_line' => 'Every legacy begins with a first chapter.',
            'closing_place' => 'Antwerpen, België · Est. 2026',
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
