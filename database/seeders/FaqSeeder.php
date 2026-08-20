<?php

namespace Database\Seeders;

use App\Enums\FaqContext;
use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            FaqContext::Product->value => [
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
            ],
            FaqContext::Contact->value => [
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
