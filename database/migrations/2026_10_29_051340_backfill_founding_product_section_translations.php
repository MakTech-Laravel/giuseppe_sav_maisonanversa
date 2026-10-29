<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Backfill remaining EN/FR section/FAQ copy for the Founding product page.
     */
    public function up(): void
    {
        $product = Product::query()->where('slug', Product::FOUNDING_SLUG)->first();

        if ($product === null) {
            return;
        }

        $product->load(['sections.items', 'faqs']);

        $sectionMap = [
            'eyebrow' => [
                'Wat in de Doos Zit' => ['en' => 'What is in the Box', 'fr' => 'Ce qui est dans la boîte'],
                'Vakmanschap' => ['en' => 'Craftsmanship', 'fr' => 'Savoir-faire'],
                'Service & Veiligheid' => ['en' => 'Service & Safety', 'fr' => 'Service & Sécurité'],
                'Vragen' => ['en' => 'Questions', 'fr' => 'Questions'],
                'Volgende Hoofdstukken' => ['en' => 'Next Chapters', 'fr' => 'Prochains chapitres'],
            ],
            'heading' => [
                'De volledige Heritage Ervaring' => ['en' => 'The complete Heritage Experience', 'fr' => "L'expérience Heritage complète"],
                'Elk detail' => ['en' => 'Every detail', 'fr' => 'Chaque détail'],
                'Met zorg geleverd.' => ['en' => 'Delivered with care.', 'fr' => 'Livré avec soin.'],
                'Veelgestelde vragen.' => ['en' => 'Frequently asked questions.', 'fr' => 'Questions fréquentes.'],
                'De volgende nummers.' => ['en' => 'The next numbers.', 'fr' => 'Les prochains numéros.'],
            ],
            'subheading' => [
                'met opzet.' => ['en' => 'by design.', 'fr' => 'avec intention.'],
            ],
            'intro' => [
                'Elke Heritage No.001 wordt geleverd als één complete ervaring. De volgorde is intentioneel.' => [
                    'en' => 'Every Heritage No.001 is delivered as one complete experience. The order is intentional.',
                    'fr' => "Chaque Heritage No.001 est livrée comme une expérience complète. L'ordre est intentionnel.",
                ],
                'Heritage No.001 wordt gebouwd met materialen die zelden in padel voorkomen. Niet voor de show — voor hoe het voelt in de hand en hoe het veroudert door de jaren.' => [
                    'en' => 'Heritage No.001 is built with materials rarely seen in padel. Not for show — for how it feels in the hand and how it ages over the years.',
                    'fr' => 'Heritage No.001 est conçue avec des matériaux rarement vus dans le padel. Pas pour le spectacle — pour le toucher et le vieillissement.',
                ],
            ],
        ];

        foreach ($product->sections as $section) {
            foreach (['eyebrow', 'heading', 'subheading', 'intro'] as $col) {
                $src = trim((string) $section->{$col});

                if ($src === '' || ! isset($sectionMap[$col][$src])) {
                    continue;
                }

                foreach ($sectionMap[$col][$src] as $locale => $value) {
                    $section->translations()->updateOrCreate(
                        ['locale' => $locale, 'column' => $col],
                        ['value' => $value, 'source_hash' => hash('sha256', $src)],
                    );
                }
            }

            foreach ($section->items as $item) {
                if ($item->title !== 'Veilig reserveren') {
                    continue;
                }

                $item->translations()->updateOrCreate(
                    ['locale' => 'en', 'column' => 'title'],
                    ['value' => 'Secure reservation', 'source_hash' => hash('sha256', (string) $item->title)],
                );
                $item->translations()->updateOrCreate(
                    ['locale' => 'fr', 'column' => 'title'],
                    ['value' => 'Réservation sécurisée', 'source_hash' => hash('sha256', (string) $item->title)],
                );
                $item->translations()->updateOrCreate(
                    ['locale' => 'en', 'column' => 'body'],
                    [
                        'value' => 'Your number is reserved after confirmation. The Founding Edition is produced in a single limited production run — fully transparent.',
                        'source_hash' => hash('sha256', (string) $item->body),
                    ],
                );
                $item->translations()->updateOrCreate(
                    ['locale' => 'fr', 'column' => 'body'],
                    [
                        'value' => 'Votre numéro est réservé après confirmation. La Founding Edition est produite en une seule série limitée — en toute transparence.',
                        'source_hash' => hash('sha256', (string) $item->body),
                    ],
                );
            }
        }

        $questions = [
            'Wanneer wordt mijn racket geleverd?' => [
                'en' => 'When will my racket be delivered?',
                'fr' => 'Quand ma raquette sera-t-elle livrée ?',
            ],
            'Hoe weet ik dat mijn nummer uniek is?' => [
                'en' => 'How do I know my number is unique?',
                'fr' => 'Comment savoir que mon numéro est unique ?',
            ],
            'Kan ik mijn nummer kiezen?' => [
                'en' => 'Can I choose my number?',
                'fr' => 'Puis-je choisir mon numéro ?',
            ],
            'Wordt Heritage No.001 opnieuw gemaakt?' => [
                'en' => 'Will Heritage No.001 be made again?',
                'fr' => 'Heritage No.001 sera-t-elle refaite ?',
            ],
        ];

        foreach ($product->faqs as $faq) {
            if (! isset($questions[$faq->question])) {
                continue;
            }

            foreach ($questions[$faq->question] as $locale => $value) {
                $faq->translations()->updateOrCreate(
                    ['locale' => $locale, 'column' => 'question'],
                    ['value' => $value, 'source_hash' => hash('sha256', $faq->question)],
                );
            }
        }
    }

    public function down(): void
    {
        //
    }
};
