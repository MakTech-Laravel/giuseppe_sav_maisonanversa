<?php

use App\Models\HomeHero;
use App\Models\LegalPage;
use App\Models\Product;
use App\Models\ProductSection;
use App\Models\ProductSectionItem;
use App\Support\Html\LegalHtml;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Apply October 2026 client wording: Founding Edition product copy,
     * EN/FR translations, and shipping/care legal pages.
     */
    public function up(): void
    {
        $this->updateFoundingProduct();
        $this->updateHomeHeroFr();
        $this->updateLegalPages();
    }

    public function down(): void
    {
        //
    }

    private function updateFoundingProduct(): void
    {
        $product = Product::query()->where('slug', Product::FOUNDING_SLUG)->first();

        if ($product === null) {
            return;
        }

        $product->fill([
            'name' => 'Heritage No.001 · Founding Edition',
            'hero_eyebrow' => 'Founding Edition · 100 Genummerde Stuks',
            'hero_subtitle' => 'Founding Edition 2026',
            'description' => 'De Founding Edition is beperkt tot 100 genummerde stuks en wordt nooit opnieuw gemaakt.',
            'expected_delivery_label' => 'Q1 2027 — ONDER VOORBEHOUD VAN PRODUCTIE',
        ])->saveQuietly();

        $this->upsertTranslations($product, [
            'name' => [
                'en' => 'Heritage No.001 · Founding Edition',
                'fr' => 'Heritage No.001 · Founding Edition',
            ],
            'hero_eyebrow' => [
                'en' => 'Founding Edition · 100 Numbered Pieces',
                'fr' => 'Founding Edition · 100 Pièces Numérotées',
            ],
            'hero_subtitle' => [
                'en' => 'Founding Edition 2026',
                'fr' => 'Founding Edition 2026',
            ],
            'description' => [
                'en' => 'The Founding Edition is limited to 100 numbered pieces and will never be made again.',
                'fr' => 'La Founding Edition est limitée à 100 pièces numérotées et ne sera jamais refaite.',
            ],
            'expected_delivery_label' => [
                'en' => 'Q1 2027 — SUBJECT TO PRODUCTION',
                'fr' => 'T1 2027 — SOUS RÉSERVE DE PRODUCTION',
            ],
        ]);

        $this->updateSectionItems($product);
        $this->updateServiceSection($product);
        $this->updateProductionFaq($product);
    }

    private function updateSectionItems(Product $product): void
    {
        $map = [
            'Oprichtersbrief' => [
                'en' => 'Founder’s Letter',
                'fr' => 'Lettre du fondateur',
            ],
            'Founding Circle lid' => [
                'en' => 'Founding Circle member',
                'fr' => 'Membre Founding Circle',
            ],
            'Welkomstkaart & Oprichtersbrief' => [
                'en' => 'Welcome Card & Founder’s Letter',
                'fr' => 'Carte de bienvenue & Lettre du fondateur',
            ],
            'Verzending in Europa' => [
                'en' => 'Shipping within Europe',
                'fr' => 'Livraison en Europe',
            ],
        ];

        foreach ($map as $dutch => $locales) {
            $items = ProductSectionItem::query()
                ->where('title', $dutch)
                ->whereHas('section', fn ($q) => $q->where('product_id', $product->id))
                ->get();

            foreach ($items as $item) {
                $this->upsertTranslations($item, [
                    'title' => $locales,
                ]);
            }
        }
    }

    private function updateServiceSection(Product $product): void
    {
        $section = ProductSection::query()
            ->where('product_id', $product->id)
            ->where('key', 'service')
            ->first();

        if ($section === null) {
            return;
        }

        $section->fill([
            'intro' => 'Elke zending is verzekerd en volgbaar. Retour binnen 14 dagen na levering.',
        ])->saveQuietly();

        $this->upsertTranslations($section, [
            'intro' => [
                'en' => 'Every shipment is insured and trackable. Returns within 14 days of delivery.',
                'fr' => 'Chaque envoi est assuré et suivi. Retours sous 14 jours après livraison.',
            ],
        ]);

        foreach ($section->items as $item) {
            if ($item->title === 'Verzending' || str_contains((string) $item->body, 'Benelux')) {
                $item->fill([
                    'title' => 'Verzending',
                    'body' => 'Binnen Europa is verzending inbegrepen. Elke zending is verzekerd en volgbaar.',
                ])->saveQuietly();

                $this->upsertTranslations($item, [
                    'title' => [
                        'en' => 'Shipping',
                        'fr' => 'Livraison',
                    ],
                    'body' => [
                        'en' => 'Shipping within Europe is included. Every shipment is insured and trackable.',
                        'fr' => 'La livraison est incluse en Europe. Chaque envoi est assuré et suivi.',
                    ],
                ]);
            }

            if ($item->title === '30 dagen retour' || str_contains((string) $item->title, '30')) {
                $item->fill([
                    'title' => '14 dagen retour',
                    'body' => 'Je hebt 14 dagen na levering om je aankoop te herroepen, zonder opgave van reden.',
                ])->saveQuietly();

                $this->upsertTranslations($item, [
                    'title' => [
                        'en' => '14-day returns',
                        'fr' => 'Retours sous 14 jours',
                    ],
                    'body' => [
                        'en' => 'You may withdraw from your purchase within 14 days of delivery, without giving a reason.',
                        'fr' => 'Vous disposez de 14 jours après la livraison pour vous rétracter, sans avoir à vous justifier.',
                    ],
                ]);
            }
        }
    }

    private function updateProductionFaq(Product $product): void
    {
        $faq = $product->faqs()
            ->where('question', 'Wanneer wordt mijn racket geleverd?')
            ->first();

        if ($faq === null) {
            return;
        }

        $answer = 'De Founding Edition wordt in één beperkte productieronde van 100 genummerde stuks vervaardigd en wordt nooit opnieuw gemaakt. Verwachte levering is Q1 2027 — onder voorbehoud van productie.';

        $faq->fill(['answer' => $answer])->saveQuietly();

        $this->upsertTranslations($faq, [
            'answer' => [
                'en' => 'The Founding Edition is produced in a single limited production run of 100 numbered pieces and will never be made again. Expected delivery is Q1 2027 — subject to production.',
                'fr' => 'La Founding Edition est produite en une seule série limitée de 100 pièces numérotées et ne sera jamais refaite. Livraison prévue au T1 2027 — sous réserve de production.',
            ],
        ]);
    }

    private function updateHomeHeroFr(): void
    {
        $hero = HomeHero::query()->first();

        if ($hero === null) {
            return;
        }

        $dutch = (string) ($hero->eyebrow ?? 'Antwerpen, België — Founding Edition 2026');

        if ($dutch === '') {
            $dutch = 'Antwerpen, België — Founding Edition 2026';
            $hero->fill(['eyebrow' => $dutch])->saveQuietly();
        }

        $this->upsertTranslations($hero, [
            'eyebrow' => [
                'en' => 'Antwerp, Belgium — Founding Edition 2026',
                'fr' => 'Anvers, Belgique — Founding Edition 2026',
            ],
        ]);
    }

    private function updateLegalPages(): void
    {
        $pages = [
            'shipping' => [
                'nl' => <<<'HTML'
<h1>Verzending &amp; Retour</h1>
<p>Elk Heritage No.001-racket wordt met de hand gecontroleerd, verpakt en verzekerd verzonden.</p>
<h2>Verzending</h2>
<p>Binnen Europa is verzending inbegrepen.</p>
<p>Elke zending is verzekerd en volgbaar. Zodra je racket vertrekt, ontvang je een e-mail met je trackinglink.</p>
<p>Buiten Europa verzenden we op aanvraag. Eventuele invoerrechten en lokale taksen zijn dan voor de koper.</p>
<h2>Bij ontvangst</h2>
<p>Controleer je pakket bij levering. Is de verpakking beschadigd of ontbreekt er iets? Laat het ons binnen 48 uur weten, met foto's, via de <a href="/nl/contact">contactpagina</a>.</p>
<h2>Retour</h2>
<p>Je hebt 14 dagen na levering om je aankoop te herroepen, zonder opgave van reden. Laat het ons binnen die termijn weten via de <a href="/nl/contact">contactpagina</a>, met je bestelnummer. Daarna heb je nog 14 dagen om het racket terug te sturen.</p>
<p>Het racket moet ongebruikt zijn: niet bespeeld, en de beschermfolie en labels zijn intact. Het moet ook volledig zijn, met het Heritage Passport, de dust bag, de rackettas en de originele verpakking.</p>
<p>De kosten van de retourzending zijn voor jou. We raden een verzekerde zending met tracking aan, want tot het racket bij ons is aangekomen, ligt het risico bij de afzender.</p>
<p>Annuleer je vóór verzending? Dan betalen we het volledige bedrag terug.</p>
<h2>De Founding Circle</h2>
<p>Je nummer wordt voor je gereserveerd zodra je bestelling betaald is. 14 dagen na levering is je inschrijving in het register definitief.</p>
<p>Stuur je het racket terug, dan komt je nummer opnieuw vrij en wordt je naam uit het register verwijderd.</p>
<h2>Terugbetaling</h2>
<p>Zodra we het racket ontvangen en gecontroleerd hebben, betalen we je terug, binnen 14 dagen, via het oorspronkelijke betaalmiddel. De oorspronkelijke standaard verzendkosten zitten daar ook in.</p>
<p>Komt het racket gebruikt, beschadigd of onvolledig terug? Dan kunnen we een vergoeding inhouden voor de waardevermindering.</p>
<p>Vragen? Neem contact met ons op via de <a href="/nl/contact">contactpagina</a>.</p>
HTML,
                'en' => <<<'HTML'
<h1>Shipping &amp; Returns</h1>
<p>Every Heritage No.001 is inspected by hand, carefully packed and shipped insured.</p>
<h2>Shipping</h2>
<p>Shipping within Europe is included.</p>
<p>Every shipment is insured and trackable. As soon as your racket leaves us, you will receive an email with your tracking link.</p>
<p>Outside Europe, shipping is available on request. Any import duties and local taxes are the responsibility of the buyer.</p>
<h2>On delivery</h2>
<p>Please check your parcel on delivery. If the packaging is damaged or something is missing, let us know within 48 hours, with photos, via the <a href="/en/contact">contact page</a>.</p>
<h2>Returns</h2>
<p>You may withdraw from your purchase within 14 days of delivery, without giving a reason. Let us know within this period via the <a href="/en/contact">contact page</a>, with your order number. You then have a further 14 days to send the racket back.</p>
<p>The racket must be unused: not played with, protective film and labels intact. It must also be complete, with the Heritage Passport, dust bag, racket case and original packaging.</p>
<p>Return shipping costs are your responsibility. We recommend an insured, tracked shipment, as the racket remains at the sender's risk until it reaches us.</p>
<p>If you cancel before shipping, we refund the full amount.</p>
<h2>The Founding Circle</h2>
<p>Your number is reserved for you as soon as your order is paid. Your inscription in the register becomes permanent 14 days after delivery.</p>
<p>If you return the racket, your number becomes available again and your name is removed from the register.</p>
<h2>Refunds</h2>
<p>Once we have received and inspected the racket, we refund you within 14 days, using your original payment method. This includes the original standard shipping costs.</p>
<p>If the racket is returned used, damaged or incomplete, we may deduct an amount for the loss in value.</p>
<p>Questions? Contact us via the <a href="/en/contact">contact page</a>.</p>
HTML,
                'fr' => <<<'HTML'
<h1>Livraison &amp; Retours</h1>
<p>Chaque Heritage No.001 est contrôlée à la main, soigneusement emballée et expédiée avec assurance.</p>
<h2>Livraison</h2>
<p>La livraison est incluse en Europe.</p>
<p>Chaque envoi est assuré et suivi. Dès que votre raquette quitte nos ateliers, vous recevez un e-mail avec votre lien de suivi.</p>
<p>Hors d'Europe, la livraison est possible sur demande. Les éventuels droits d'importation et taxes locales sont à la charge de l'acheteur.</p>
<h2>À la réception</h2>
<p>Vérifiez votre colis à la livraison. Si l'emballage est endommagé ou s'il manque quelque chose, prévenez-nous dans les 48 heures, avec photos, via la <a href="/fr/contact">page contact</a>.</p>
<h2>Retours</h2>
<p>Vous disposez de 14 jours après la livraison pour vous rétracter, sans avoir à vous justifier. Informez-nous dans ce délai via la <a href="/fr/contact">page contact</a>, avec votre numéro de commande. Vous avez ensuite 14 jours pour nous renvoyer la raquette.</p>
<p>La raquette doit être inutilisée : non jouée, film de protection et étiquettes intacts. Elle doit aussi être complète, avec le Heritage Passport, la housse, l'étui et l'emballage d'origine.</p>
<p>Les frais de retour sont à votre charge. Nous recommandons un envoi assuré et suivi, car la raquette reste sous la responsabilité de l'expéditeur jusqu'à sa réception.</p>
<p>En cas d'annulation avant l'expédition, nous vous remboursons l'intégralité du montant.</p>
<h2>Le Founding Circle</h2>
<p>Votre numéro vous est réservé dès le paiement de votre commande. Votre inscription au registre devient définitive 14 jours après la livraison.</p>
<p>Si vous retournez la raquette, votre numéro redevient disponible et votre nom est retiré du registre.</p>
<h2>Remboursement</h2>
<p>Dès réception et contrôle de la raquette, nous vous remboursons sous 14 jours, via votre moyen de paiement d'origine. Les frais de livraison standard initiaux sont inclus.</p>
<p>Si la raquette nous revient utilisée, endommagée ou incomplète, nous pouvons retenir un montant correspondant à la dépréciation.</p>
<p>Des questions ? Contactez-nous via la <a href="/fr/contact">page contact</a>.</p>
HTML,
            ],
            'care' => [
                'nl' => <<<'HTML'
<h1>Zorg &amp; Garantie</h1>
<p>Heritage No.001 is gebouwd om lang mee te gaan. Met de juiste zorg blijft het jaren mooi en speelklaar.</p>
<h2>Het slagvlak</h2>
<p>Reinig het matte oppervlak en het reliëf van het huis met een zachte, droge doek.</p>
<p>Gebruik geen water, zeep, alcohol of oplosmiddelen, en schrob niet over het reliëf.</p>
<h2>De lederen grip</h2>
<p>Laat de grip na het spelen aan de lucht drogen.</p>
<p>Verzorg het leder twee keer per jaar met een neutrale lederbalsem.</p>
<p>Leder werkt en verkleurt licht met de tijd. Dat hoort bij het karakter van het materiaal.</p>
<h2>Bewaring</h2>
<p>Bewaar je racket in zijn tas of dust bag, op een droge plaats en uit direct zonlicht.</p>
<p>Laat het nooit in een warme auto liggen. Hitte kan de kern van EVA aantasten.</p>
<h2>Garantie</h2>
<p>Je hebt recht op de wettelijke garantie van 2 jaar vanaf levering. Die dekt fabricagefouten en gebreken die al aanwezig waren bij levering.</p>
<p>Niet gedekt zijn normale slijtage van de grip en het oppervlak, krassen, schade door een slag tegen de muur, de vloer of een ander racket, barsten door impact, schade door verkeerde bewaring zoals hitte of vocht, en aanpassingen aan het racket.</p>
<p>Een garantievraag? Stuur ons via de <a href="/nl/contact">contactpagina</a> je bestelnummer, het serienummer uit je Heritage Passport (Nº 001–100) en duidelijke foto's. We bekijken elke vraag persoonlijk en zoeken een oplossing: herstelling, vervanging of terugbetaling, zoals de wet voorziet.</p>
<p>Bewaar je Heritage Passport goed. Het is je bewijs van echtheid en van aankoop.</p>
<p>Vragen? Neem contact met ons op via de <a href="/nl/contact">contactpagina</a>.</p>
HTML,
                'en' => <<<'HTML'
<h1>Care &amp; Warranty</h1>
<p>Heritage No.001 is built to last. With the right care, it will stay beautiful and ready to play for years.</p>
<h2>The face</h2>
<p>Clean the matte surface and the relief of the house with a soft, dry cloth.</p>
<p>Do not use water, soap, alcohol or solvents, and do not scrub the relief.</p>
<h2>The leather grip</h2>
<p>Let the grip air-dry after play.</p>
<p>Treat the leather twice a year with a neutral leather balm.</p>
<p>Leather softens and darkens slightly over time. This is part of the character of the material.</p>
<h2>Storage</h2>
<p>Keep your racket in its case or dust bag, in a dry place and out of direct sunlight.</p>
<p>Never leave it in a hot car. Heat can affect the EVA core.</p>
<h2>Warranty</h2>
<p>You are entitled to the 2-year legal guarantee from delivery. It covers manufacturing defects and defects that were present on delivery.</p>
<p>It does not cover normal wear of the grip and surface, scratches, damage from impact against walls, floors or other rackets, cracks caused by impact, damage from improper storage such as heat or moisture, or modifications to the racket.</p>
<p>Warranty question? Send us your order number, the serial number from your Heritage Passport (Nº 001–100) and clear photos via the <a href="/en/contact">contact page</a>. We review every request personally and find a solution: repair, replacement or refund, as provided by law.</p>
<p>Keep your Heritage Passport safe. It is your proof of authenticity and purchase.</p>
<p>Questions? Contact us via the <a href="/en/contact">contact page</a>.</p>
HTML,
                'fr' => <<<'HTML'
<h1>Entretien &amp; Garantie</h1>
<p>Heritage No.001 est conçue pour durer. Avec un entretien adapté, elle restera belle et prête à jouer pendant des années.</p>
<h2>La face</h2>
<p>Nettoyez la surface mate et le relief de la maison avec un chiffon doux et sec.</p>
<p>N'utilisez ni eau, ni savon, ni alcool, ni solvant, et ne frottez pas le relief.</p>
<h2>Le grip en cuir</h2>
<p>Laissez sécher le grip à l'air libre après le jeu.</p>
<p>Nourrissez le cuir deux fois par an avec un baume neutre.</p>
<p>Le cuir s'assouplit et fonce légèrement avec le temps. Cela fait partie du caractère de la matière.</p>
<h2>Rangement</h2>
<p>Rangez votre raquette dans son étui ou sa housse, dans un endroit sec et à l'abri du soleil.</p>
<p>Ne la laissez jamais dans une voiture chaude. La chaleur peut altérer le noyau en EVA.</p>
<h2>Garantie</h2>
<p>Vous bénéficiez de la garantie légale de 2 ans à compter de la livraison. Elle couvre les défauts de fabrication et les défauts existant à la livraison.</p>
<p>Elle ne couvre pas l'usure normale du grip et de la surface, les rayures, les dommages dus à un choc contre un mur, le sol ou une autre raquette, les fissures causées par un impact, les dommages dus à un mauvais rangement (chaleur, humidité), ni les modifications de la raquette.</p>
<p>Une question de garantie ? Envoyez-nous via la <a href="/fr/contact">page contact</a> votre numéro de commande, le numéro de série de votre Heritage Passport (Nº 001–100) et des photos claires. Nous examinons chaque demande personnellement et trouvons une solution : réparation, remplacement ou remboursement, conformément à la loi.</p>
<p>Conservez précieusement votre Heritage Passport. C'est votre preuve d'authenticité et d'achat.</p>
<p>Des questions ? Contactez-nous via la <a href="/fr/contact">page contact</a>.</p>
HTML,
            ],
        ];

        foreach ($pages as $slug => $bodies) {
            $page = LegalPage::query()->where('slug', $slug)->first();

            if ($page === null) {
                continue;
            }

            $nl = LegalHtml::sanitize($bodies['nl']);
            $page->fill(['body' => $nl, 'is_published' => true])->saveQuietly();

            $this->upsertRawTranslation($page, 'en', 'body', LegalHtml::sanitize($bodies['en']), $nl);
            $this->upsertRawTranslation($page, 'fr', 'body', LegalHtml::sanitize($bodies['fr']), $nl);
        }
    }

    /**
     * @param  array<string, array{en: string, fr: string}>  $columns
     */
    private function upsertTranslations(object $model, array $columns): void
    {
        foreach ($columns as $column => $locales) {
            $source = (string) $model->{$column};
            $hash = hash('sha256', $source);

            foreach ($locales as $locale => $value) {
                DB::table('translations')->updateOrInsert(
                    [
                        'translatable_type' => $model::class,
                        'translatable_id' => $model->id,
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

    private function upsertRawTranslation(object $model, string $locale, string $column, string $value, string $source): void
    {
        DB::table('translations')->updateOrInsert(
            [
                'translatable_type' => $model::class,
                'translatable_id' => $model->id,
                'locale' => $locale,
                'column' => $column,
            ],
            [
                'value' => $value,
                'source_hash' => hash('sha256', $source),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
};
