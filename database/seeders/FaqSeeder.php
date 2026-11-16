<?php

namespace Database\Seeders;

use App\Enums\FaqContext;
use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Contact FAQs only. Product FAQs are owned by each product and seeded
     * through ProductSeeder into `product_faqs`.
     */
    public function run(): void
    {
        $rows = [
            FaqContext::Contact->value => [
                [
                    'question' => 'Wanneer levert Heritage Nº 001?',
                    'answer' => 'Levering is gepland in Q1 2027. De Founding Edition levert in volgorde van reservering — hoe vroeger je reserveert, hoe lager jouw nummer.',
                ],
                [
                    'question' => 'Hoe wordt mijn racket geleverd?',
                    'answer' => 'In een handgemaakte omslag, vergezeld van een certificaat van echtheid en je genummerde editienummer.',
                ],
                [
                    'question' => 'Kan ik mijn racket retourneren?',
                    'answer' => 'Retourneren binnen 14 dagen na levering via Mijn account → Bestellingen → Retourneren, of via /returns. Stuur alles mee wat erbij hoorde.',
                ],
                [
                    'question' => 'Is de prijs inclusief verzending?',
                    'answer' => 'Levering binnen de EU is inbegrepen.',
                ],
                [
                    'question' => 'Hoe onderhoud ik het leder?',
                    'answer' => 'Behandel het leder tweemaal per jaar met een neutrale lederbalsem. Vermijd langdurig vocht en direct zonlicht.',
                ],
                [
                    'question' => 'Kan ik het racket personaliseren?',
                    'answer' => 'De Founding Edition is genummerd. Verdere personalisatie volgt in latere edities.',
                ],
            ],
        ];

        foreach ($rows as $context => $items) {
            foreach ($items as $sort => $item) {
                Faq::query()->updateOrCreate(
                    ['context' => $context, 'question' => $item['question']],
                    [
                        'answer' => $item['answer'],
                        'sort_order' => $sort,
                        'is_published' => true,
                    ],
                );
            }
        }
    }
}
