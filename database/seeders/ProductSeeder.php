<?php

namespace Database\Seeders;

use App\Enums\ProductSectionKey;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductSection;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the Founding Edition catalog product with storefront content.
     * Stripe IDs are created on first purchase or when Stripe keys are present.
     */
    public function run(): void
    {
        $product = Product::query()->updateOrCreate(
            ['slug' => Product::FOUNDING_SLUG],
            [
                'name' => 'Heritage No.001 — Founding Edition',
                'type' => ProductType::LimitedEdition,
                'status' => ProductStatus::Active,
                'amount' => '249.00',
                'currency' => 'eur',
                'edition_total' => 100,
                'archive_edition_numbers' => [1],
                'is_published' => true,
                'grants_founding_circle' => true,
                'expected_delivery_label' => 'Q1 2027 — ONDER VOORBEHOUD VAN PRODUCTIE',
                'eyebrow' => 'Maison Anversa · Founding Edition',
                'hero_eyebrow' => 'Founding Edition · 100 Stuks Wereldwijd',
                'hero_subtitle' => 'Het eerste hoofdstuk van Maison Anversa. Beperkt tot 100 stuks. Elk genummerd. De Founding Edition wordt nooit herhaald.',
                'description' => 'Heritage No.001 is niet zomaar een padelracket. Het is het eerste object van een huis dat wordt gebouwd voor de lange termijn. Elk van de 100 stuks is individueel genummerd en wordt vergezeld van een volledige Heritage ervaring.',
                'sort_order' => 0,
                'gallery' => [
                    'heritage-001-front',
                    'heritage-001-detail-gravure',
                    'atelier-workshop',
                    'heritage-001-lifestyle-court',
                ],
            ],
        );

        foreach (self::sections() as $key => $definition) {
            $section = ProductSection::query()->updateOrCreate(
                ['product_id' => $product->id, 'key' => $key],
                [
                    'eyebrow' => $definition['eyebrow'] ?? null,
                    'heading' => $definition['heading'] ?? null,
                    'subheading' => $definition['subheading'] ?? null,
                    'intro' => $definition['intro'] ?? null,
                    'image_key' => $definition['image_key'] ?? null,
                    'is_visible' => true,
                    'sort_order' => ProductSectionKey::from($key)->defaultSortOrder(),
                ],
            );

            $section->items()->delete();

            foreach ($definition['items'] ?? [] as $sort => $item) {
                $section->items()->create([
                    'number_label' => $item['number_label'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'title' => $item['title'] ?? null,
                    'body' => $item['body'] ?? null,
                    'sort_order' => $sort,
                ]);
            }
        }

        foreach (self::faqs() as $sort => $faq) {
            $product->faqs()->updateOrCreate(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'sort_order' => $sort,
                    'is_published' => true,
                ],
            );
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function sections(): array
    {
        return [
            ProductSectionKey::Specs->value => [
                'items' => [
                    ['title' => 'Frame', 'body' => '3K Carbon'],
                    ['title' => 'Kern', 'body' => 'EVA Soft'],
                    ['title' => 'Greep', 'body' => 'Echt leder, handafgewerkt'],
                    ['title' => 'Afwerking', 'body' => 'Matte premium — Crème / Chocolade'],
                    ['title' => 'Gewicht', 'body' => 'Finale specificatie volgt na prototypecontrole'],
                    ['title' => 'Nummering', 'body' => 'Individueel gestempeld 001–100'],
                    ['title' => 'Editie', 'body' => 'Founding Edition — éénmalig'],
                ],
            ],
            ProductSectionKey::Includes->value => [
                'items' => [
                    ['title' => 'Heritage No.001 racket (individueel genummerd)'],
                    ['title' => 'Heritage Certificaat met oprichterzegel'],
                    ['title' => "Heritage Paspoort (24 pagina's)"],
                    ['title' => 'Welkomstkaart & Oprichtersbrief'],
                    ['title' => 'Founding Circle uitnodiging'],
                    ['title' => 'Premium canvas stofdoek'],
                ],
            ],
            ProductSectionKey::Guarantees->value => [
                'items' => [
                    ['icon' => '◇', 'title' => 'Veilige reservering'],
                    ['icon' => '◆', 'title' => 'Wereldwijde verzending'],
                    ['icon' => '◈', 'title' => 'Authenticiteit gegarandeerd'],
                ],
            ],
            ProductSectionKey::Unboxing->value => [
                'eyebrow' => 'Wat in de Doos Zit',
                'heading' => 'De volledige Heritage Ervaring',
                'intro' => 'Elke Heritage No.001 wordt geleverd als één complete ervaring. De volgorde is intentioneel.',
                'items' => [
                    [
                        'number_label' => '01',
                        'title' => 'Welkomstkaart',
                        'body' => 'A5 · 400g katoenpapier · Letterpress · Handtekening oprichter',
                    ],
                    [
                        'number_label' => '02',
                        'title' => 'Oprichtersbrief',
                        'body' => 'Het verhaal van Maison Anversa. Persoonlijk, authentiek.',
                    ],
                    [
                        'number_label' => '03',
                        'title' => 'Heritage Paspoort',
                        'body' => "A6 · 24 pagina's · Vegetaal gelooide lederen omslag",
                    ],
                    [
                        'number_label' => '04',
                        'title' => 'Heritage Certificaat',
                        'body' => 'A5 · Letterpress + goudfolie · Oprichterzegel · Editienummer',
                    ],
                    [
                        'number_label' => '05',
                        'title' => 'Founding Circle',
                        'body' => 'Uitnodiging voor de permanente gemeenschap van de eerste 100.',
                    ],
                    [
                        'number_label' => '06',
                        'title' => 'Heritage No.001',
                        'body' => 'Premium canvas stofdoek · MA monogram · Individueel genummerd',
                    ],
                ],
            ],
            ProductSectionKey::Craft->value => [
                'eyebrow' => 'Vakmanschap',
                'heading' => 'Elk detail',
                'subheading' => 'met opzet.',
                'intro' => 'Heritage No.001 wordt gebouwd met materialen die zelden in padel voorkomen. Niet voor de show — voor hoe het voelt in de hand en hoe het veroudert door de jaren.',
                'image_key' => 'atelier-workshop',
                'items' => [
                    [
                        'number_label' => '01',
                        'title' => '3K Carbon Frame',
                        'body' => 'De meest veeleisende weave in de industrie. Licht, stijf, en zichtbaar vakmanschap in elk raster.',
                    ],
                    [
                        'number_label' => '02',
                        'title' => 'Echt Lederen Greep',
                        'body' => 'Handafgewerkt, vegetaal gelooid. Wordt mooier met gebruik — synthetisch kan dat niet.',
                    ],
                    [
                        'number_label' => '03',
                        'title' => 'EVA Soft Kern',
                        'body' => 'Een balans tussen comfort en controle. Gekalibreerd voor zowel gevoel als kracht.',
                    ],
                    [
                        'number_label' => '04',
                        'title' => 'Goudfolie & Letterpress',
                        'body' => 'Certificaat en paspoort gedrukt met traditionele technieken. Geen laserprint.',
                    ],
                ],
            ],
            ProductSectionKey::Trust->value => [
                'items' => [
                    ['icon' => '◈', 'title' => 'Genummerd 001–100'],
                    ['icon' => '✓', 'title' => 'Echtheids-certificaat'],
                    ['icon' => '◆', 'title' => 'Ontworpen in Antwerpen'],
                    ['icon' => '★', 'title' => 'Founding Circle lid'],
                ],
            ],
            ProductSectionKey::Service->value => [
                'eyebrow' => 'Service & Veiligheid',
                'heading' => 'Met zorg geleverd.',
                'intro' => 'Voorgenomen servicebeleid — definitief bij lancering.',
                'items' => [
                    [
                        'icon' => '◆',
                        'title' => 'Verzending',
                        'body' => 'Gratis verzekerd verzonden binnen de Benelux. EU-levering in 3–5 werkdagen. Elk pakket handmatig gecontroleerd en verzegeld.',
                    ],
                    [
                        'icon' => '↺',
                        'title' => '30 dagen retour',
                        'body' => 'Niet overtuigd? Retour binnen 30 dagen, mits ongebruikt. Volledige terugbetaling, zonder vragen.',
                    ],
                    [
                        'icon' => '◇',
                        'title' => 'Veilig reserveren',
                        'body' => 'Uw nummer wordt vastgelegd na bevestiging. De Founding Edition wordt in één beperkte productieronde vervaardigd — volledig transparant.',
                    ],
                ],
            ],
            ProductSectionKey::Faq->value => [
                'eyebrow' => 'Vragen',
                'heading' => 'Veelgestelde vragen.',
            ],
            ProductSectionKey::Related->value => [
                'eyebrow' => 'Volgende Hoofdstukken',
                'heading' => 'De volgende nummers.',
            ],
        ];
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private static function faqs(): array
    {
        return [
            [
                'question' => 'Wanneer wordt mijn racket geleverd?',
                'answer' => 'De Founding Edition wordt in één beperkte productieronde van 100 stuks vervaardigd. Bestellingen worden geleverd na definitieve kwaliteitscontrole en goedkeuring van de productie. Verwachte levering is Q1 2027. U ontvangt tussentijds updates over de voortgang.',
            ],
            [
                'question' => 'Hoe weet ik dat mijn nummer uniek is?',
                'answer' => 'Elk racket is individueel gestempeld (001–100) en vergezeld van een Heritage Certificaat met hetzelfde nummer en het oprichterzegel. Het nummer staat ook in ons register.',
            ],
            [
                'question' => 'Kan ik mijn nummer kiezen?',
                'answer' => 'Binnen de beschikbare nummers kunt u een voorkeur opgeven bij reservering. Leden van de Founding Circle hebben voorrang op lagere nummers.',
            ],
            [
                'question' => 'Wordt Heritage No.001 opnieuw gemaakt?',
                'answer' => 'Nee. De Founding Edition wordt niet herhaald — 100 stuks, eenmalig. Heritage No.001 kan daarna als reguliere collectie beschikbaar blijven. Toekomstige releases dragen andere nummers (No.002, No.003).',
            ],
        ];
    }
}
