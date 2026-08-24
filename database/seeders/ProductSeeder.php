<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the Founding Edition catalog product with storefront content.
     * Stripe IDs are created on first purchase or when Stripe keys are present.
     */
    public function run(): void
    {
        Product::query()->updateOrCreate(
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
                'specs' => [
                    ['label' => 'Frame', 'value' => '3K Carbon'],
                    ['label' => 'Kern', 'value' => 'EVA Soft'],
                    ['label' => 'Greep', 'value' => 'Echt leder, handafgewerkt'],
                    ['label' => 'Afwerking', 'value' => 'Matte premium — Crème / Chocolade'],
                    ['label' => 'Gewicht', 'value' => 'Finale specificatie volgt na prototypecontrole'],
                    ['label' => 'Nummering', 'value' => 'Individueel gestempeld 001–100'],
                    ['label' => 'Editie', 'value' => 'Founding Edition — éénmalig'],
                ],
                'materials' => [
                    [
                        'num' => '01',
                        'name' => '3K Carbon Frame',
                        'desc' => 'De meest veeleisende weave in de industrie. Licht, stijf, en zichtbaar vakmanschap in elk raster.',
                    ],
                    [
                        'num' => '02',
                        'name' => 'Echt Lederen Greep',
                        'desc' => 'Handafgewerkt, vegetaal gelooid. Wordt mooier met gebruik — synthetisch kan dat niet.',
                    ],
                    [
                        'num' => '03',
                        'name' => 'EVA Soft Kern',
                        'desc' => 'Een balans tussen comfort en controle. Gekalibreerd voor zowel gevoel als kracht.',
                    ],
                    [
                        'num' => '04',
                        'name' => 'Goudfolie & Letterpress',
                        'desc' => 'Certificaat en paspoort gedrukt met traditionele technieken. Geen laserprint.',
                    ],
                ],
                'unboxing_steps' => [
                    [
                        'num' => '01',
                        'title' => 'Welkomstkaart',
                        'desc' => 'A5 · 400g katoenpapier · Letterpress · Handtekening oprichter',
                    ],
                    [
                        'num' => '02',
                        'title' => 'Oprichtersbrief',
                        'desc' => 'Het verhaal van Maison Anversa. Persoonlijk, authentiek.',
                    ],
                    [
                        'num' => '03',
                        'title' => 'Heritage Paspoort',
                        'desc' => "A6 · 24 pagina's · Vegetaal gelooide lederen omslag",
                    ],
                    [
                        'num' => '04',
                        'title' => 'Heritage Certificaat',
                        'desc' => 'A5 · Letterpress + goudfolie · Oprichterzegel · Editienummer',
                    ],
                    [
                        'num' => '05',
                        'title' => 'Founding Circle',
                        'desc' => 'Uitnodiging voor de permanente gemeenschap van de eerste 100.',
                    ],
                    [
                        'num' => '06',
                        'title' => 'Heritage No.001',
                        'desc' => 'Premium canvas stofdoek · MA monogram · Individueel genummerd',
                    ],
                ],
                'includes' => [
                    'Heritage No.001 racket (individueel genummerd)',
                    'Heritage Certificaat met oprichterzegel',
                    "Heritage Paspoort (24 pagina's)",
                    'Welkomstkaart & Oprichtersbrief',
                    'Founding Circle uitnodiging',
                    'Premium canvas stofdoek',
                ],
                'guarantees' => [
                    ['icon' => '◇', 'text' => 'Veilige reservering'],
                    ['icon' => '◆', 'text' => 'Wereldwijde verzending'],
                    ['icon' => '◈', 'text' => 'Authenticiteit gegarandeerd'],
                ],
                'trust_badges' => [
                    ['icon' => '◈', 'text' => 'Genummerd 001–100'],
                    ['icon' => '✓', 'text' => 'Echtheids-certificaat'],
                    ['icon' => '◆', 'text' => 'Ontworpen in Antwerpen'],
                    ['icon' => '★', 'text' => 'Founding Circle lid'],
                ],
            ],
        );
    }
}
