<?php

use App\Models\Faq;
use App\Models\HomeHero;
use App\Models\LegalPage;
use App\Models\Product;
use App\Models\ProductSection;
use App\Models\ProductSectionItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Client review "Translation review staging 2nd V" (Nov 2026).
 *
 * Content only: EU-only delivery, 14-day returns, no A6 / 24 pages on the Heritage
 * Passport, no atelier, brand names that stay English, informal Dutch ("je"), and
 * the Antwerp corner that is open instead of "in discussion".
 *
 * Every step matches rows by their current text, so it is safe to run on a database
 * that was already corrected and on one that still carries the old copy.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->fixBrandTerms();
        $this->fixFoundingProduct();
        $this->fixFaq();
        $this->fixHomeHeroCounter();
        $this->openAntwerpCorner();
        $this->fixJournalTitle();
        $this->fixLegalPages();
    }

    public function down(): void
    {
        //
    }

    /**
     * Brand names are never translated: Heritage Passport / Certificate / Letter, Founding Circle.
     * Fixes translations that DeepL or the old dictionaries wrote (ERFGOED NR., OPRICHTERSKRING…).
     */
    private function fixBrandTerms(): void
    {
        $rules = [
            '/erfgoed\s+nr\.?\s*(\d{3})/iu' => fn (array $m): string => 'Heritage Nº '.$m[1],
            '/patrimoine\s+n[°º]\s*(\d{3})/iu' => fn (array $m): string => 'Heritage Nº '.$m[1],
            '/oprichterskring/iu' => fn (): string => 'Founding Circle',
            '/cercle\s+des\s+fondateurs/iu' => fn (): string => 'Founding Circle',
            '/deksel\s+van\s+de\s+founding\s+circle/iu' => fn (): string => 'Lid van de Founding Circle',
            '/passeport\s+du\s+patrimoine|passeport\s+héritage|heritage\s+paspoort/iu' => fn (): string => 'Heritage Passport',
            '/certificat\s+[«"]\s*heritage\s*[»"]|certificat\s+héritage|heritage\s+certificaat/iu' => fn (): string => 'Heritage Certificate',
            '/lettre\s+du\s+patrimoine|lettre\s+héritage|heritage\s+brief/iu' => fn (): string => 'Heritage Letter',
            // Wording fixes from the review (French and Dutch phrases DeepL got wrong).
            '/Édition\s*«\s*Founding\s*»\s*—\s*en édition unique/u' => fn (): string => 'Founding Edition — édition unique',
            '/Pour en route jusqu[’\']au court/u' => fn (): string => 'Pour le trajet jusqu’au court',
            '/Moulins italiens premium/u' => fn (): string => 'Filatures italiennes premium',
            '/qui performant sur le terrain/u' => fn (): string => 'qui performent sur le court',
            '/Comment pouvons-nous aider\s*\?/u' => fn (): string => 'Comment pouvons-nous vous aider ?',
            '/\bDe Journal\b/u' => fn (): string => 'Het Journal',
            '/Premium Italiaanse mills/u' => fn (): string => 'Premium Italiaanse weverijen',
        ];

        $needles = [
            'erfgoed', 'patrimoine', 'oprichterskring', 'cercle des fondateurs', 'deksel', 'passeport', 'paspoort',
            'certificat', 'certificaat', 'lettre héritage', 'heritage brief', 'en édition unique', 'en route jusqu',
            'moulins italiens', 'performant sur', 'pouvons-nous aider', 'De Journal', 'Italiaanse mills',
        ];

        $apply = function (string $value) use ($rules): string {
            foreach ($rules as $pattern => $replace) {
                $value = (string) preg_replace_callback($pattern, function (array $m) use ($replace): string {
                    $new = $replace($m);

                    return mb_strtoupper($m[0]) === $m[0] && mb_strtolower($m[0]) !== $m[0]
                        ? mb_strtoupper($new)
                        : $new;
                }, $value);
            }

            return $value;
        };

        $targets = [
            'translations' => ['value'],
            'products' => ['name', 'hero_eyebrow', 'hero_subtitle', 'description'],
            'product_sections' => ['heading', 'intro'],
            'product_section_items' => ['title', 'body'],
            'product_faqs' => ['question', 'answer'],
            'faqs' => ['question', 'answer'],
            'dressing_items' => ['name', 'description'],
            'seo_metas' => ['title', 'description'],
        ];

        foreach ($targets as $table => $columns) {
            foreach ($columns as $column) {
                $query = DB::table($table)->whereNotNull($column)->where(function ($q) use ($column, $needles): void {
                    foreach ($needles as $needle) {
                        $q->orWhere($column, 'like', "%{$needle}%");
                    }
                });

                foreach ($query->get(['id', $column]) as $row) {
                    $old = (string) $row->{$column};
                    $new = $apply($old);

                    if ($new !== $old) {
                        DB::table($table)->where('id', $row->id)->update([$column => $new]);
                    }
                }
            }
        }
    }

    private function fixFoundingProduct(): void
    {
        $product = DB::table('products')->where('slug', Product::FOUNDING_SLUG)->first();

        if ($product === null) {
            return;
        }

        $sections = DB::table('product_sections')->where('product_id', $product->id)->get()->keyBy('key');

        $setItem = function (string $sectionKey, string $titlePattern, array $source, array $translations) use ($sections): void {
            $section = $sections->get($sectionKey);

            if ($section === null) {
                return;
            }

            $items = DB::table('product_section_items')
                ->where('product_section_id', $section->id)
                ->get()
                ->filter(fn (object $item): bool => preg_match($titlePattern, (string) $item->title.' '.(string) $item->body) === 1);

            foreach ($items as $item) {
                if ($source !== []) {
                    DB::table('product_section_items')->where('id', $item->id)->update($source);
                }

                $current = (object) [...(array) $item, ...$source];
                $this->upsertTranslations(ProductSectionItem::class, (int) $item->id, $current, $translations);
            }
        };

        $includes = [
            ['/certificaat|certificate/i', ['title' => 'Heritage Certificate met zegel van de oprichter'], ['title' => ['en' => 'Heritage Certificate with founder’s seal', 'fr' => 'Heritage Certificate avec sceau du fondateur']]],
            ['/paspoort|passport/i', ['title' => 'Heritage Passport'], ['title' => ['en' => 'Heritage Passport', 'fr' => 'Heritage Passport']]],
            ['/welkomstkaart\s*&|welcome card\s*&/i', ['title' => 'Welkomstkaart & brief van de oprichter'], ['title' => ['en' => 'Welcome Card & Founder’s Letter', 'fr' => 'Carte de bienvenue & Lettre du fondateur']]],
            ['/stofdoek|stofzak|dust (cover|bag)/i', ['title' => 'Premium canvas stofzak'], ['title' => ['en' => 'Premium canvas dust bag', 'fr' => 'Housse en toile premium']]],
        ];

        foreach ($includes as [$pattern, $source, $translations]) {
            $setItem('includes', $pattern, $source, $translations);
        }

        $setItem('unboxing', '/oprichtersbrief|brief van de oprichter|founder.?s letter/i', [
            'title' => 'Brief van de oprichter',
            'body' => 'Het verhaal van Maison Anversa. Persoonlijk, authentiek.',
        ], [
            'title' => ['en' => 'Founder’s Letter', 'fr' => 'Lettre du fondateur'],
            'body' => ['en' => 'The story of Maison Anversa. Personal, authentic.', 'fr' => 'L’histoire de Maison Anversa. Personnelle, authentique.'],
        ]);

        $setItem('unboxing', '/paspoort|passport/i', [
            'title' => 'Heritage Passport',
            'body' => 'Vegetaal gelooide lederen omslag',
        ], [
            'title' => ['en' => 'Heritage Passport', 'fr' => 'Heritage Passport'],
            'body' => ['en' => 'Vegetable-tanned leather cover', 'fr' => 'Couverture en cuir tanné végétal'],
        ]);

        $setItem('unboxing', '/certificaat|certificate/i', [
            'title' => 'Heritage Certificate',
            'body' => 'A5 · Letterpress + goudfolie · Zegel van de oprichter · Editienummer',
        ], [
            'title' => ['en' => 'Heritage Certificate', 'fr' => 'Heritage Certificate'],
            'body' => ['en' => 'A5 · Letterpress + gold foil · Founder’s seal · Edition number', 'fr' => 'A5 · Letterpress + dorure · Sceau du fondateur · Numéro d’édition'],
        ]);

        $setItem('unboxing', '/stofdoek|stofzak|dust (cover|bag)/i', [
            'body' => 'Premium canvas stofzak · MA monogram · Individueel genummerd',
        ], [
            'body' => ['en' => 'Premium canvas dust bag · MA monogram · Individually numbered', 'fr' => 'Housse en toile premium · Monogramme MA · Numérotée individuellement'],
        ]);

        $setItem('guarantees', '/verzending in europa|wereldwijde? (verzending|levering)|worldwide (shipping|delivery)|livraison dans le monde|shipping (in|within) europe|livraison en europe/i', [
            'title' => 'Levering in de EU',
        ], [
            'title' => ['en' => 'Delivery within the EU', 'fr' => 'Livraison dans l’UE'],
        ]);

        $setItem('trust', '/founding circle lid|deksel|lid van de founding circle|circle member/i', [
            'title' => 'Lid van de Founding Circle',
        ], [
            'title' => ['en' => 'Founding Circle member', 'fr' => 'Membre du Founding Circle'],
        ]);

        $setItem('craft', '/echt lederen greep|genuine leather grip/i', [
            'title' => 'Echt lederen greep',
        ], [
            'title' => ['en' => 'Genuine leather grip', 'fr' => 'Poignée en cuir véritable'],
        ]);

        $service = $sections->get('service');

        if ($service !== null) {
            $intro = 'Elke zending is verzekerd en traceerbaar. Retour binnen 14 dagen na levering.';

            DB::table('product_sections')->where('id', $service->id)->update(['intro' => $intro]);
            $this->upsertTranslations(ProductSection::class, (int) $service->id, (object) ['intro' => $intro], [
                'intro' => [
                    'en' => 'Every shipment is insured and traceable. Returns within 14 days of delivery.',
                    'fr' => 'Chaque envoi est assuré et traçable. Retours sous 14 jours après livraison.',
                ],
            ]);

            $this->dropServicePolicyNotices((int) $service->id);

            $setItem('service', '/^(verzending|levering|shipping|delivery|livraison)\b|benelux|wereldwijd|worldwide/i', [
                'title' => 'Verzending',
                'body' => 'Levering binnen de EU is inbegrepen. Elke zending is verzekerd en traceerbaar.',
            ], [
                'title' => ['en' => 'Shipping', 'fr' => 'Livraison'],
                'body' => ['en' => 'Delivery within the EU is included. Every shipment is insured and trackable.', 'fr' => 'La livraison dans l’UE est incluse. Chaque envoi est assuré et suivi.'],
            ]);

            $setItem('service', '/\bretour\b|\breturns?\b|\bretours\b|30 dag|30-day|30 jours/i', [
                'title' => '14 dagen retour',
                'body' => 'Je hebt 14 dagen na levering om je aankoop te herroepen, zonder opgave van reden.',
            ], [
                'title' => ['en' => '14-day returns', 'fr' => 'Retours sous 14 jours'],
                'body' => ['en' => 'You may withdraw from your purchase within 14 days of delivery, without giving a reason.', 'fr' => 'Vous disposez de 14 jours après la livraison pour vous rétracter, sans avoir à vous justifier.'],
            ]);

            $setItem('service', '/reserveren|reservering|reserve|réserv|uw nummer|je nummer/i', [
                'body' => 'Je nummer wordt vastgelegd na bevestiging. De Founding Edition wordt in één beperkte productieronde vervaardigd — volledig transparant.',
            ], [
                'body' => ['en' => 'Your number is locked in after confirmation. The Founding Edition is made in a single limited production run — fully transparent.', 'fr' => 'Votre numéro est attribué après confirmation. La Founding Edition est fabriquée en une seule série limitée — en toute transparence.'],
            ]);
        }
    }

    /**
     * The "proposed service policy — final at launch" notice is outdated: the policy is final.
     */
    private function dropServicePolicyNotices(int $sectionId): void
    {
        $notices = DB::table('product_section_items')
            ->where('product_section_id', $sectionId)
            ->where(function ($query): void {
                foreach (['voorgenomen', 'proposed service', 'politique de service proposée'] as $needle) {
                    $query->orWhere('title', 'like', "%{$needle}%")->orWhere('body', 'like', "%{$needle}%");
                }
            })
            ->pluck('id');

        if ($notices->isEmpty()) {
            return;
        }

        DB::table('translations')
            ->where('translatable_type', ProductSectionItem::class)
            ->whereIn('translatable_id', $notices)
            ->delete();

        DB::table('product_section_items')->whereIn('id', $notices)->delete();
    }

    private function fixFaq(): void
    {
        $shipping = DB::table('faqs')->where('answer', 'like', '%Buiten Europa%')->orWhere('answer', 'like', '%Benelux%')->get();

        foreach ($shipping as $faq) {
            $answer = 'Levering binnen de EU is inbegrepen.';

            DB::table('faqs')->where('id', $faq->id)->update(['answer' => $answer]);
            $this->upsertTranslations(Faq::class, (int) $faq->id, (object) ['answer' => $answer], [
                'answer' => ['en' => 'Delivery within the EU is included.', 'fr' => 'La livraison dans l’UE est incluse.'],
            ]);
        }

        $informal = [
            'uw genummerd editienummer' => 'je genummerde editienummer',
            'kunt u een voorkeur opgeven' => 'kun je een voorkeur opgeven',
            'hoe vroeger u reserveert, hoe lager uw nummer' => 'hoe vroeger je reserveert, hoe lager jouw nummer',
            'het oprichterzegel' => 'het zegel van de oprichter',
        ];

        foreach (['faqs', 'product_faqs'] as $table) {
            foreach (DB::table($table)->get(['id', 'answer']) as $faq) {
                $answer = strtr((string) $faq->answer, $informal);

                if ($answer !== (string) $faq->answer) {
                    DB::table($table)->where('id', $faq->id)->update(['answer' => $answer]);
                }
            }
        }
    }

    private function fixHomeHeroCounter(): void
    {
        $hero = DB::table('home_heroes')->first();

        if ($hero === null) {
            return;
        }

        $this->upsertTranslations(HomeHero::class, (int) $hero->id, $hero, [
            'counter_line_one' => ['en' => 'Numbers still', 'fr' => 'Numéros encore'],
            'counter_line_two' => ['en' => 'available', 'fr' => 'disponibles'],
        ]);
    }

    private function openAntwerpCorner(): void
    {
        DB::table('clubs')
            ->where('city', 'Antwerpen')
            ->where('corner_pipeline_status', 'in_discussion')
            ->update(['corner_pipeline_status' => 'open']);

        DB::table('clubs')
            ->where('name', 'Neem contact op')
            ->update(['name' => 'Get in touch', 'city' => 'Get in touch']);
    }

    private function fixJournalTitle(): void
    {
        foreach (['title', 'body'] as $column) {
            foreach (DB::table('journal_articles')->where($column, 'like', '%het meest sociale sport%')->get(['id', $column]) as $row) {
                DB::table('journal_articles')->where('id', $row->id)->update([
                    $column => str_replace('het meest sociale sport', 'de meest sociale sport', (string) $row->{$column}),
                ]);
            }
        }
    }

    /**
     * Shipping and terms pages: delivery is inside the EU only. The full Terms and
     * Privacy texts are replaced later, once the final copy is delivered.
     */
    private function fixLegalPages(): void
    {
        $outside = '#\s*<p>[^<]*(?:Buiten Europa|Outside Europe|Hors d[\'’]Europe)[^<]*</p>#u';
        $replacements = [
            'nl' => ['Binnen Europa is verzending inbegrepen.' => 'Binnen de EU is verzending inbegrepen.', 'het zorg- en garantiebeleid en de verzend- en retourbeleid' => 'het zorg- en garantiebeleid en het verzend- en retourbeleid'],
            'en' => ['Shipping within Europe is included.' => 'Shipping within the EU is included.'],
            'fr' => ['La livraison est incluse en Europe.' => 'La livraison est incluse dans l’UE.', 'quitte nos ateliers' => 'est expédiée'],
        ];

        foreach (DB::table('legal_pages')->get() as $page) {
            $body = $this->cleanLegal((string) $page->body, $outside, $replacements['nl']);

            if ($body !== (string) $page->body) {
                DB::table('legal_pages')->where('id', $page->id)->update(['body' => $body]);
            }

            foreach (['en', 'fr'] as $locale) {
                $row = DB::table('translations')
                    ->where('translatable_type', LegalPage::class)
                    ->where('translatable_id', $page->id)
                    ->where('locale', $locale)
                    ->where('column', 'body')
                    ->first();

                if ($row === null) {
                    continue;
                }

                $clean = $this->cleanLegal((string) $row->value, $outside, $replacements[$locale]);

                if ($clean !== (string) $row->value) {
                    DB::table('translations')->where('id', $row->id)->update([
                        'value' => $clean,
                        'source_hash' => hash('sha256', $body),
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function cleanLegal(string $html, string $outsidePattern, array $replacements): string
    {
        $html = (string) preg_replace($outsidePattern, '', $html);

        return strtr($html, $replacements);
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, array{en: string, fr: string}>  $columns
     */
    private function upsertTranslations(string $modelClass, int $id, object $source, array $columns): void
    {
        foreach ($columns as $column => $locales) {
            $hash = hash('sha256', (string) ($source->{$column} ?? ''));

            foreach ($locales as $locale => $value) {
                DB::table('translations')->updateOrInsert(
                    [
                        'translatable_type' => $modelClass,
                        'translatable_id' => $id,
                        'locale' => $locale,
                        'column' => $column,
                    ],
                    [
                        'value' => $value,
                        'source_hash' => $hash,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }
    }
};
