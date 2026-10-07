<?php

namespace App\Models;

use App\Enums\HomeHeroAction;
use App\Models\Concerns\TranslatesWithDeepL;
use Illuminate\Database\Eloquent\Model;

class HomeHero extends Model
{
    use TranslatesWithDeepL;

    /**
     * @var list<string>
     */
    public const SLOTS = ['primary', 'secondary', 'tertiary'];

    /**
     * Dutch source columns DeepL may translate. Actions, targets, and the
     * photograph stay on the row itself.
     *
     * @var list<string>
     */
    public const TRANSLATION_COLUMNS = [
        'eyebrow',
        'title',
        'title_accent',
        'tagline',
        'counter_line_one',
        'counter_line_two',
        'primary_label',
        'secondary_label',
        'tertiary_label',
    ];

    /**
     * @var list<string>
     */
    protected array $translatable = self::TRANSLATION_COLUMNS;

    /**
     * @var list<string>
     */
    protected array $translationExcept = [
        'image_path',
        'primary_action',
        'primary_target',
        'secondary_action',
        'secondary_target',
        'tertiary_action',
        'tertiary_target',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'image_path',
        'eyebrow',
        'title',
        'title_accent',
        'tagline',
        'show_counter',
        'counter_line_one',
        'counter_line_two',
        'primary_label',
        'primary_action',
        'primary_target',
        'secondary_label',
        'secondary_action',
        'secondary_target',
        'tertiary_label',
        'tertiary_action',
        'tertiary_target',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_counter' => 'boolean',
            'primary_action' => HomeHeroAction::class,
            'secondary_action' => HomeHeroAction::class,
            'tertiary_action' => HomeHeroAction::class,
        ];
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
     * Today's Dutch hero copy. English and French come from DeepL.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'image_path' => null,
            'eyebrow' => 'Antwerpen, België — Founding Edition 2026',
            'title' => 'Maison',
            'title_accent' => 'Anversa',
            'tagline' => 'European Heritage Sports & Lifestyle House',
            'show_counter' => true,
            'counter_line_one' => 'Nummers nog',
            'counter_line_two' => 'beschikbaar',
            'primary_label' => 'Ontdek Heritage No.001 →',
            'primary_action' => HomeHeroAction::FoundingProduct,
            'primary_target' => null,
            'secondary_label' => 'Betreed het Huis',
            'secondary_action' => HomeHeroAction::MaisonPage,
            'secondary_target' => 'house',
            'tertiary_label' => 'Heritage Letter',
            'tertiary_action' => HomeHeroAction::Newsletter,
            'tertiary_target' => null,
        ];
    }
}
