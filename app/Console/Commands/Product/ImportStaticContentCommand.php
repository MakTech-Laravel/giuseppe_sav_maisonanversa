<?php

namespace App\Console\Commands\Product;

use App\Enums\FaqContext;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Faq;
use App\Models\Product;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:import-static-content-command')]
#[Description('Import static Maison content into database')]
class ImportStaticContentCommand extends Command
{
    public function handle(): int
    {
        $founding = Product::query()->updateOrCreate(
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

        Product::query()->updateOrCreate(
            ['slug' => 'heritage-no-002'],
            [
                'name' => 'Heritage No.002',
                'type' => ProductType::LimitedEdition,
                'status' => ProductStatus::ComingSoon,
                'amount' => '0.00',
                'currency' => 'eur',
                'edition_total' => 0,
                'archive_edition_numbers' => [],
                'is_published' => true,
                'grants_founding_circle' => false,
                'sort_order' => 1,
                'gallery' => ['heritage-001-front'],
                'eyebrow' => 'Maison Anversa · Coming Soon',
                'hero_eyebrow' => 'Volgende release',
                'hero_subtitle' => 'Volgende release · Aankondiging via de Heritage Letter',
                'description' => '',
            ],
        );

        Product::query()->updateOrCreate(
            ['slug' => 'heritage-no-003'],
            [
                'name' => 'Heritage No.003',
                'type' => ProductType::LimitedEdition,
                'status' => ProductStatus::ComingSoon,
                'amount' => '0.00',
                'currency' => 'eur',
                'edition_total' => 0,
                'archive_edition_numbers' => [],
                'is_published' => true,
                'grants_founding_circle' => false,
                'sort_order' => 2,
                'gallery' => ['heritage-001-lifestyle-court'],
                'eyebrow' => 'Maison Anversa · Coming Soon',
                'hero_eyebrow' => 'In voorbereiding',
                'hero_subtitle' => 'In voorbereiding · Geen datum bekend',
                'description' => '',
            ],
        );

        foreach ($this->productFaqRows() as $index => $faq) {
            Faq::query()->updateOrCreate(
                ['context' => FaqContext::Product, 'question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }

        foreach ($this->contactFaqRows() as $index => $faq) {
            Faq::query()->updateOrCreate(
                ['context' => FaqContext::Contact, 'question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }

        $this->info("Imported static content for {$founding->name}.");

        return self::SUCCESS;
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function productFaqRows(): array
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

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function contactFaqRows(): array
    {
        return [
            [
                'question' => 'Wanneer levert Heritage No.001?',
                'answer' => 'Levering is gepland in Q1 2027. De Founding Edition levert in volgorde van reservering — hoe vroeger u reserveert, hoe lager uw nummer.',
            ],
            [
                'question' => 'Hoe wordt mijn racket geleverd?',
                'answer' => 'In een handgemaakte omslag, vergezeld van een certificaat van echtheid en uw genummerd editienummer.',
            ],
            [
                'question' => 'Kan ik mijn racket retourneren?',
                'answer' => 'Retourneren binnen 14 dagen na levering, mits ongebruikt. Het definitieve retourbeleid wordt vastgelegd bij lancering.',
            ],
            [
                'question' => 'Is de prijs inclusief verzending?',
                'answer' => 'Verzending binnen Europa is inbegrepen. Buiten Europa op aanvraag.',
            ],
            [
                'question' => 'Hoe onderhoud ik het leder?',
                'answer' => 'Behandel het leder tweemaal per jaar met een neutrale lederbalsem. Vermijd langdurig vocht en direct zonlicht.',
            ],
            [
                'question' => 'Kan ik het racket personaliseren?',
                'answer' => 'De Founding Edition is genummerd. Verdere personalisatie volgt in latere edities.',
            ],
        ];
    }
}
