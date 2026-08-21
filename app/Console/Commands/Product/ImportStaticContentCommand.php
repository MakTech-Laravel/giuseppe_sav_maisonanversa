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
                'hero_subtitle' => 'Het eerste hoofdstuk van Maison Anversa. Beperkt tot 100 stuks. Elk genummerd. De Founding Edition wordt nooit herhaald.',
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
                    'Full Carbon Frame — 3K weave',
                    'Premium 3K Carbon Surface',
                    'Echte Lederen Greep',
                ],
                'unboxing_steps' => [
                    'Atelier verzegeling en nummercontrole',
                    'Heritage Certificaat met oprichterzegel',
                    "Heritage Paspoort met 24 pagina's",
                    'Welkomstkaart en oprichtersbrief',
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
                    'Authenticiteit gegarandeerd',
                    'Heritage Certificaat + Paspoort',
                    'Founding Circle uitnodiging',
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
                'hero_subtitle' => 'Volgende release · Aankondiging via de Heritage Letter',
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
                'hero_subtitle' => 'In voorbereiding · Geen datum bekend',
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
