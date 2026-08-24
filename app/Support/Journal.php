<?php

namespace App\Support;

use App\Models\JournalArticle;
use Database\Seeders\JournalArticleSeeder;

/**
 * The Maison Anversa Journal: a thin facade over {@see JournalArticle} so the
 * index, the detail page and the sitemap cannot drift apart.
 *
 * Dutch remains the source language on the model; English and French live in
 * the translations table (seeded from {@see staticCatalog()} or via DeepL).
 *
 * @phpstan-type LocaleCopy array{nl: string, en: string, fr: string}
 * @phpstan-type Article array{
 *     slug: string,
 *     asset: string|null,
 *     image_url: string|null,
 *     category: LocaleCopy,
 *     title: LocaleCopy,
 *     excerpt: LocaleCopy,
 *     author: string,
 *     date: LocaleCopy,
 *     body: LocaleCopy|list<LocaleCopy>
 * }
 */
final class Journal
{
    public const PER_PAGE = 6;

    /**
     * @return list<Article>
     */
    public static function articles(): array
    {
        return JournalArticle::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (JournalArticle $article): array => $article->toCatalogArray())
            ->all();
    }

    /**
     * @return Article|null
     */
    public static function find(string $slug): ?array
    {
        $article = JournalArticle::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        return $article?->toCatalogArray();
    }

    /**
     * @param  Article  $article
     * @return array{
     *     slug: string,
     *     asset: string,
     *     image_url: string|null,
     *     category: string,
     *     title: string,
     *     excerpt: string,
     *     author: string,
     *     date: string,
     *     meta: string,
     *     body: list<string>
     * }
     */
    public static function localize(array $article, string $locale): array
    {
        $pick = fn (array $copy): string => $copy[$locale] ?? $copy['nl'];

        $date = $pick($article['date']);
        $body = self::localizeBody($article['body'], $pick);

        return [
            'slug' => $article['slug'],
            'asset' => $article['asset'] ?: 'antwerp-cityscape',
            'image_url' => $article['image_url'],
            'category' => $pick($article['category']),
            'title' => $pick($article['title']),
            'excerpt' => $pick($article['excerpt']),
            'author' => $article['author'],
            'date' => $date,
            'meta' => $article['author'].' · '.$date,
            'body' => $body,
        ];
    }

    /**
     * @param  Article  $article
     * @return array{slug: string, asset: string, image_url: string|null, category: string, title: string, excerpt: string, author: string, date: string, meta: string}
     */
    public static function card(array $article, string $locale): array
    {
        $localised = self::localize($article, $locale);
        unset($localised['body']);

        return $localised;
    }

    /**
     * The next pieces in the catalog, wrapping around so the last article
     * still has neighbours.
     *
     * @return list<array{slug: string, asset: string, image_url: string|null, category: string, title: string, excerpt: string, author: string, date: string, meta: string}>
     */
    public static function related(string $slug, string $locale, int $limit = 3): array
    {
        $catalog = self::articles();
        $index = array_search($slug, array_column($catalog, 'slug'), true);

        if ($index === false) {
            return [];
        }

        $related = [];
        $count = count($catalog);

        for ($offset = 1; count($related) < $limit && $offset < $count; $offset++) {
            $related[] = self::card($catalog[($index + $offset) % $count], $locale);
        }

        return $related;
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_column(self::articles(), 'slug');
    }

    /**
     * @return list<array{slug: string, lastmod: string|null}>
     */
    public static function sitemapArticles(): array
    {
        return JournalArticle::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['slug', 'updated_at', 'published_at'])
            ->map(fn (JournalArticle $article): array => [
                'slug' => $article->slug,
                'lastmod' => ($article->updated_at ?? $article->published_at)?->toAtomString(),
            ])
            ->all();
    }

    /**
     * Join paragraph LocaleCopy lists into a single LocaleCopy string for storage.
     *
     * @param  list<LocaleCopy>  $paragraphs
     * @return LocaleCopy
     */
    public static function joinBodyParagraphs(array $paragraphs): array
    {
        $locales = ['nl', 'en', 'fr'];
        $joined = [];

        foreach ($locales as $locale) {
            $joined[$locale] = implode("\n\n", array_map(
                fn (array $copy): string => $copy[$locale] ?? $copy['nl'],
                $paragraphs,
            ));
        }

        return $joined;
    }

    /**
     * @param  LocaleCopy|list<LocaleCopy>  $body
     * @param  callable(LocaleCopy): string  $pick
     * @return list<string>
     */
    private static function localizeBody(array $body, callable $pick): array
    {
        if ($body === []) {
            return [];
        }

        if (isset($body['nl']) || isset($body['en']) || isset($body['fr'])) {
            /** @var LocaleCopy $body */
            $text = trim($pick($body));

            if ($text === '') {
                return [];
            }

            return preg_split("/\n\n+/", $text) ?: [$text];
        }

        /** @var list<LocaleCopy> $body */
        return array_map($pick, $body);
    }

    /**
     * Static editorial catalog used by {@see JournalArticleSeeder}
     * and `journal:import-static`. Prefer {@see articles()} for runtime reads.
     *
     * @return list<Article>
     */
    public static function staticCatalog(): array
    {
        $heritage = ['nl' => 'Erfgoed', 'en' => 'Heritage', 'fr' => 'Patrimoine'];
        $sport = ['nl' => 'Sport & Cultuur', 'en' => 'Sport & Culture', 'fr' => 'Sport & Culture'];
        $design = ['nl' => 'Design', 'en' => 'Design', 'fr' => 'Design'];
        $craft = ['nl' => 'Vakmanschap', 'en' => 'Craft', 'fr' => 'Savoir-faire'];
        $circle = ['nl' => 'Founding Circle', 'en' => 'Founding Circle', 'fr' => 'Founding Circle'];
        $materials = ['nl' => 'Materialen', 'en' => 'Materials', 'fr' => 'Matériaux'];
        $yusuf = 'Yusuf Savran';
        $house = 'Maison Anversa';

        return [
            [
                'slug' => 'waarom-antwerpen-luxewereld',
                'asset' => 'antwerp-cityscape',
                'category' => $heritage,
                'title' => [
                    'nl' => 'Waarom Antwerpen de meest ondervertegenwoordigde stad in de luxewereld is',
                    'en' => 'Why Antwerp is the most underrepresented city in the luxury world',
                    'fr' => 'Pourquoi Anvers est la ville la plus sous-représentée du luxe',
                ],
                'excerpt' => [
                    'nl' => 'Een stad met eeuwen van diamantambacht, Vlaamse Meesters en culturele ambitie — en toch geen enkel wereldmerk dat het draagt.',
                    'en' => 'A city with centuries of diamond craft, Flemish Masters and cultural ambition — and yet no world brand that carries it.',
                    'fr' => 'Une ville de diamantaires, de maîtres flamands et d’ambition culturelle — et pourtant aucune maison mondiale qui la porte.',
                ],
                'author' => $yusuf,
                'date' => ['nl' => 'Juni 2026', 'en' => 'June 2026', 'fr' => 'Juin 2026'],
                'body' => [
                    [
                        'nl' => 'Antwerpen heeft alles wat een erfgoedhuis nodig heeft: een haven die de wereld binnenliet, een diamantwijk die precisie tot cultuur maakte, en een burgerlijk zelfbewustzijn dat nooit luid hoefde te zijn. Toch draagt bijna geen enkel luxemerk de stad in zijn naam. Parijs, Milaan, Londen — de kaart is vol. Antwerpen blijft een leegte.',
                        'en' => 'Antwerp has everything a heritage house needs: a port that let the world in, a diamond quarter that made precision a culture, and a civic self-possession that never had to shout. Yet almost no luxury house carries the city in its name. Paris, Milan, London — the map is full. Antwerp remains a blank.',
                        'fr' => 'Anvers a tout ce qu’une maison d’héritage exige : un port ouvert sur le monde, un quartier diamantaire qui a fait de la précision une culture, et une retenue bourgeoise qui n’a jamais eu besoin de crier. Presque aucune maison de luxe ne porte pourtant la ville dans son nom.',
                    ],
                    [
                        'nl' => 'Die leegte is geen toeval. Antwerpen verkoopt zichzelf niet. Het is een stad van makers, van ateliers achter gevels, van families die hun vak doorgeven zonder persbericht. Precies daarom hoort een huis als Maison Anversa hier — niet als import, maar als iets dat hier groeit.',
                        'en' => 'That blank is not an accident. Antwerp does not sell itself. It is a city of makers, of workshops behind façades, of families who pass on a craft without a press release. That is why a house like Maison Anversa belongs here — not as an import, but as something that grows from this ground.',
                        'fr' => 'Ce vide n’est pas un hasard. Anvers ne se vend pas. C’est une ville d’ateliers derrière les façades, de familles qui transmettent un métier sans communiqué. C’est pourquoi Maison Anversa appartient ici.',
                    ],
                    [
                        'nl' => 'Wij bouwen geen sportmerk dat toevallig in België is gevestigd. Wij bouwen een Europees erfgoedhuis dat Antwerpen draagt — in het leder, in de numerotering, in de manier waarop we spreken. De stad die ons draagt, verdient een huis dat haar naam waardig is.',
                        'en' => 'We are not building a sports brand that happens to be based in Belgium. We are building a European heritage house that carries Antwerp — in the leather, in the numbering, in the way we speak. The city that carries us deserves a house worthy of its name.',
                        'fr' => 'Nous ne construisons pas une marque de sport qui se trouve être belge. Nous construisons une maison européenne qui porte Anvers — dans le cuir, dans la numérotation, dans la voix.',
                    ],
                ],
            ],
            [
                'slug' => 'padel-meest-sociale-sport',
                'asset' => 'heritage-001-lifestyle-court',
                'category' => $sport,
                'title' => [
                    'nl' => 'Waarom padel het meest sociale sport ter wereld is — en wat dat betekent voor gemeenschap',
                    'en' => 'Why padel is the most social sport in the world — and what that means for community',
                    'fr' => 'Pourquoi le padel est le sport le plus social du monde — et ce que cela signifie pour une communauté',
                ],
                'excerpt' => [
                    'nl' => 'Padel is geen tennis. Het is een ritueel. Een manier van samenkomen die andere sporten niet kennen.',
                    'en' => 'Padel is not tennis. It is a ritual. A way of gathering that other sports do not know.',
                    'fr' => 'Le padel n’est pas du tennis. C’est un rituel. Une façon de se retrouver que les autres sports ignorent.',
                ],
                'author' => $house,
                'date' => ['nl' => 'Mei 2026', 'en' => 'May 2026', 'fr' => 'Mai 2026'],
                'body' => [
                    [
                        'nl' => 'Vier spelers, één kooi, geen tribune. Padel dwingt nabijheid af. Je hoort de adem van je partner. Je deelt het punt, het verlies, de grap na afloop. Dat is geen bijwerking van de sport — het is de sport.',
                        'en' => 'Four players, one cage, no grandstand. Padel insists on proximity. You hear your partner breathe. You share the point, the loss, the joke afterwards. That is not a side-effect of the sport — it is the sport.',
                        'fr' => 'Quatre joueurs, une cage, pas de tribune. Le padel impose la proximité. On entend le souffle de son partenaire. On partage le point, la défaite, la blague ensuite.',
                    ],
                    [
                        'nl' => 'Tennis is een duel. Padel is een gesprek. Clubs die dat begrijpen, bouwen geen rijen banen. Zij bouwen een huis: een plek waar je blijft hangen, waar de koffie even belangrijk is als de smash.',
                        'en' => 'Tennis is a duel. Padel is a conversation. Clubs that understand this do not build rows of courts. They build a house: a place you linger, where the coffee matters as much as the smash.',
                        'fr' => 'Le tennis est un duel. Le padel est une conversation. Les clubs qui l’ont compris ne construisent pas des rangées de courts. Ils construisent une maison.',
                    ],
                    [
                        'nl' => 'Maison Anversa bestaat omdat die gemeenschap een taal verdient die even verzorgd is als het spel. Heritage No.001 is daarvoor gemaakt — niet om te schreeuwen op de baan, maar om tot de mensen te behoren die na de set nog blijven.',
                        'en' => 'Maison Anversa exists because that community deserves a language as considered as the game. Heritage No.001 was made for that — not to shout on court, but to belong to the people who stay after the set.',
                        'fr' => 'Maison Anversa existe parce que cette communauté mérite un langage aussi soigné que le jeu. Heritage No.001 a été fait pour cela.',
                    ],
                ],
            ],
            [
                'slug' => 'quiet-luxury-sport',
                'asset' => 'atelier-workshop',
                'category' => $design,
                'title' => [
                    'nl' => 'Quiet Luxury — wat het is, waarom het groeit, en waarom het perfect is voor sport',
                    'en' => 'Quiet Luxury — what it is, why it grows, and why it is perfect for sport',
                    'fr' => 'Quiet Luxury — ce que c’est, pourquoi cela grandit, et pourquoi c’est parfait pour le sport',
                ],
                'excerpt' => [
                    'nl' => 'De beweging weg van logo\'s naar kwaliteit is niet tijdelijk. Het is een fundamentele verschuiving in hoe mensen luxe definiëren.',
                    'en' => 'The move away from logos toward quality is not temporary. It is a fundamental shift in how people define luxury.',
                    'fr' => 'Le passage des logos à la qualité n’est pas temporaire. C’est un changement profond dans la définition du luxe.',
                ],
                'author' => $house,
                'date' => ['nl' => 'April 2026', 'en' => 'April 2026', 'fr' => 'Avril 2026'],
                'body' => [
                    [
                        'nl' => 'Quiet luxury is geen trendkleur. Het is een weigering: geen logo dat het werk doet, geen seizoen dat het vorige uitwist. Wat overblijft is materiaal, snit, en de tijd die iemand erin heeft gestoken.',
                        'en' => 'Quiet luxury is not a trend colour. It is a refusal: no logo doing the work, no season erasing the last. What remains is material, cut, and the time someone put into it.',
                        'fr' => 'Le quiet luxury n’est pas une couleur de saison. C’est un refus : pas de logo qui travaille à votre place, pas de saison qui efface la précédente.',
                    ],
                    [
                        'nl' => 'Op de baan is die weigering nog scherper. Sportkleding schreeuwt van nature. Wie fluistert, valt op zonder te vragen. Een racket zonder opschrift, een polo zonder borstlogo — dat is geen bescheidenheid. Dat is zelfvertrouwen.',
                        'en' => 'On court the refusal is sharper still. Sportswear shouts by default. Whoever whispers is noticed without asking. A racket without a billboard, a polo without a chest logo — that is not modesty. It is confidence.',
                        'fr' => 'Sur le court, ce refus est plus net encore. L’équipement sportif crie par défaut. Celui qui murmure se fait remarquer sans demander.',
                    ],
                    [
                        'nl' => 'Maison Anversa kiest die stilte met opzet. Chocolade, goud, crème, zwart. Dezelfde taal als het huis. Wie het herkent, herkent het. Wie het niet herkent, was sowieso niet de gesprekspartner.',
                        'en' => 'Maison Anversa chooses that silence on purpose. Chocolate, gold, cream, black. The same language as the house. Those who recognise it, recognise it. Those who do not were never the conversation.',
                        'fr' => 'Maison Anversa choisit ce silence. Chocolat, or, crème, noir. La même langue que la maison.',
                    ],
                ],
            ],
            [
                'slug' => '3k-carbon-heritage',
                'asset' => 'heritage-001-detail-gravure',
                'category' => $craft,
                'title' => [
                    'nl' => '3K Carbon — waarom wij kozen voor de meest veeleisende weave in de industrie',
                    'en' => '3K Carbon — why we chose the most demanding weave in the industry',
                    'fr' => 'Carbone 3K — pourquoi nous avons choisi l’armure la plus exigeante de l’industrie',
                ],
                'excerpt' => [
                    'nl' => 'Niet elke carbon is gelijk. De keuze voor 3K carbon bij Heritage No.001 was een bewuste — en dure — beslissing.',
                    'en' => 'Not all carbon is equal. The choice of 3K carbon for Heritage No.001 was a deliberate — and expensive — decision.',
                    'fr' => 'Tous les carbones ne se valent pas. Le 3K de Heritage No.001 fut une décision délibérée — et coûteuse.',
                ],
                'author' => $house,
                'date' => ['nl' => 'Maart 2026', 'en' => 'March 2026', 'fr' => 'Mars 2026'],
                'body' => [
                    [
                        'nl' => '3K betekent drieduizend filamenten per tow. Fijner, trager, duurder om te leggen. De meeste rackets kiezen grovere weaves omdat de lijn sneller loopt. Wij kozen 3K omdat je het voelt voordat je het ziet.',
                        'en' => '3K means three thousand filaments per tow. Finer, slower, more expensive to lay. Most rackets choose coarser weaves because the line runs faster. We chose 3K because you feel it before you see it.',
                        'fr' => 'Le 3K, ce sont trois mille filaments par tow. Plus fin, plus lent, plus cher à poser. La plupart des raquettes choisissent plus gros parce que la ligne va plus vite.',
                    ],
                    [
                        'nl' => 'Het oppervlak van Heritage No.001 is geen decor. Het is de structuur die het gewicht verdeelt, de twist weigert, en de gravure een grond geeft. Wie het racket in handen neemt, begrijpt de prijs zonder brochure.',
                        'en' => 'The face of Heritage No.001 is not decoration. It is the structure that spreads the weight, refuses twist, and gives the engraving a ground. Whoever holds the racket understands the price without a brochure.',
                        'fr' => 'La face de Heritage No.001 n’est pas un décor. C’est la structure qui répartit le poids et offre un sol à la gravure.',
                    ],
                    [
                        'nl' => 'Vakmanschap is kiezen voor de weg die langer duurt. Niet omdat langzaam romantisch is, maar omdat het resultaat niet anders kan. De Founding Edition wordt nooit herhaald. Het carbon mag dat waardig zijn.',
                        'en' => 'Craft is choosing the longer road. Not because slow is romantic, but because the result cannot be otherwise. The Founding Edition will never be repeated. The carbon should be worthy of that.',
                        'fr' => 'Le savoir-faire, c’est choisir le chemin plus long. L’édition fondatrice ne se répétera jamais. Le carbone doit en être digne.',
                    ],
                ],
            ],
            [
                'slug' => 'nummer-001-founding-circle',
                'asset' => 'heritage-001-front',
                'category' => $circle,
                'title' => [
                    'nl' => 'Wat het betekent om nummer 001 te zijn — over de Founding Circle en de eerste 100',
                    'en' => 'What it means to be number 001 — about the Founding Circle and the first 100',
                    'fr' => 'Ce que signifie être le numéro 001 — le Founding Circle et les cent premiers',
                ],
                'excerpt' => [
                    'nl' => 'Er is iets bijzonders aan de mensen die ergens in geloven voor de rest het weet. De Founding Circle is voor hen.',
                    'en' => 'There is something particular about the people who believe in something before the rest know. The Founding Circle is for them.',
                    'fr' => 'Il y a quelque chose de singulier chez ceux qui croient avant les autres. Le Founding Circle est pour eux.',
                ],
                'author' => $yusuf,
                'date' => ['nl' => 'Februari 2026', 'en' => 'February 2026', 'fr' => 'Février 2026'],
                'body' => [
                    [
                        'nl' => 'Nummer 001 is geen marketing. Het is een belofte aan de eerste honderd: jullie waren er toen het huis nog een tekening was. Jullie nummer wordt niet opnieuw uitgegeven. De Founding Edition stopt bij 100.',
                        'en' => 'Number 001 is not marketing. It is a promise to the first hundred: you were there when the house was still a drawing. Your number will not be issued again. The Founding Edition stops at 100.',
                        'fr' => 'Le numéro 001 n’est pas du marketing. C’est une promesse aux cent premiers : vous étiez là quand la maison n’était encore qu’un dessin.',
                    ],
                    [
                        'nl' => 'De Founding Circle is geen loyaliteitsprogramma. Het is een permanente gemeenschap. Toegang tot de Kleedkamer voor de rest, sessies, het Journal, een plek in het verhaal van het huis. Wie nummer draagt, draagt het huis.',
                        'en' => 'The Founding Circle is not a loyalty scheme. It is a permanent community. Access to the Dressing Room before the rest, sessions, the Journal, a place in the story of the house. Whoever wears a number, wears the house.',
                        'fr' => 'Le Founding Circle n’est pas un programme de fidélité. C’est une communauté permanente. Porter un numéro, c’est porter la maison.',
                    ],
                    [
                        'nl' => 'Ik heb Maison Anversa niet gebouwd voor iedereen. Ik heb het gebouwd voor de mensen die het verschil voelen tussen iets dat gemaakt is en iets dat geproduceerd is. De eerste honderd zijn die mensen. Hun namen horen bij het huis.',
                        'en' => 'I did not build Maison Anversa for everyone. I built it for the people who feel the difference between something made and something produced. The first hundred are those people. Their names belong to the house.',
                        'fr' => 'Je n’ai pas bâti Maison Anversa pour tout le monde. Les cent premiers sont ceux qui sentent la différence entre ce qui est fait et ce qui est produit.',
                    ],
                ],
            ],
            [
                'slug' => 'terugkeer-van-leder',
                'asset' => 'hero-mansion',
                'category' => $materials,
                'title' => [
                    'nl' => 'De terugkeer van leder in sport — hoe een traditioneel materiaal de toekomst definieert',
                    'en' => 'The return of leather in sport — how a traditional material defines the future',
                    'fr' => 'Le retour du cuir dans le sport — comment une matière ancienne définit l’avenir',
                ],
                'excerpt' => [
                    'nl' => 'Synthetische grepen domineren de markt. Wij kozen voor echt leder. Niet voor nostalgie — maar voor kwaliteit.',
                    'en' => 'Synthetic grips dominate the market. We chose real leather. Not for nostalgia — but for quality.',
                    'fr' => 'Les grips synthétiques dominent le marché. Nous avons choisi le vrai cuir. Pas par nostalgie — par exigence.',
                ],
                'author' => $house,
                'date' => ['nl' => 'Januari 2026', 'en' => 'January 2026', 'fr' => 'Janvier 2026'],
                'body' => [
                    [
                        'nl' => 'Leder wordt warm in de hand. Synthetisch blijft vreemd, hoe goed de perforatie ook is. Voor een racket dat je jaren houdt, is dat geen detail. Het is het eerste wat je voelt en het laatste wat je vergeet.',
                        'en' => 'Leather warms in the hand. Synthetic stays foreign, however fine the perforation. For a racket you keep for years, that is not a detail. It is the first thing you feel and the last you forget.',
                        'fr' => 'Le cuir se réchauffe dans la main. Le synthétique reste étranger. Pour une raquette que l’on garde des années, ce n’est pas un détail.',
                    ],
                    [
                        'nl' => 'Wij werken met vegetaal gelooid leder — geen chroombad, geen haast. Het patineert. Het krijgt uw handschrift. Een Heritage-grip na een seizoen is niet versleten. Hij is begonnen.',
                        'en' => 'We work with vegetable-tanned leather — no chrome bath, no hurry. It patinates. It takes your handwriting. A Heritage grip after a season is not worn out. It has begun.',
                        'fr' => 'Nous travaillons un cuir tanné végétal. Il patine. Il prend votre écriture. Après une saison, un grip Heritage n’est pas usé. Il a commencé.',
                    ],
                    [
                        'nl' => 'De toekomst van sportmateriaal is niet meer plastic. Het is minder, beter, en gemaakt om te blijven. Leder is ouder dan de sport. Precies daarom hoort het bij wat hierna komt.',
                        'en' => 'The future of sports equipment is not more plastic. It is less, better, and made to remain. Leather is older than the sport. That is exactly why it belongs to what comes next.',
                        'fr' => 'L’avenir du matériel n’est pas plus de plastique. C’est moins, mieux, et fait pour durer. Le cuir est plus ancien que le sport.',
                    ],
                ],
            ],
            [
                'slug' => 'club-corner-derde-plek',
                'asset' => 'room-courtyard',
                'category' => $sport,
                'title' => [
                    'nl' => 'Club Corner — waarom de club de derde plek van het huis is',
                    'en' => 'Club Corner — why the club is the third room of the house',
                    'fr' => 'Club Corner — pourquoi le club est la troisième pièce de la maison',
                ],
                'excerpt' => [
                    'nl' => 'Niet elke padelclub is een verkooppunt. De juiste club is een kamer van Maison Anversa — met dezelfde taal als het racket.',
                    'en' => 'Not every padel club is a point of sale. The right club is a room of Maison Anversa — speaking the same language as the racket.',
                    'fr' => 'Tous les clubs ne sont pas un point de vente. Le bon club est une pièce de Maison Anversa.',
                ],
                'author' => $house,
                'date' => ['nl' => 'December 2025', 'en' => 'December 2025', 'fr' => 'Décembre 2025'],
                'body' => [
                    [
                        'nl' => 'Een Club Corner is geen rek met product. Het is een hoek waar het huis zichtbaar wordt: display, demo, paspoort, het gesprek. Leden mogen aanraken voordat ze reserveren. Dat is de volgorde die past bij iets dat beperkt is.',
                        'en' => 'A Club Corner is not a rack of product. It is a corner where the house becomes visible: display, demo, passport, the conversation. Members may touch before they reserve. That is the order that fits something limited.',
                        'fr' => 'Un Club Corner n’est pas un présentoir. C’est un angle où la maison devient visible. On touche avant de réserver.',
                    ],
                    [
                        'nl' => 'Wij selecteren clubs zoals wij materialen selecteren. Niet op omzet. Op sfeer, op hoe de koffie wordt gezet, op of de baan een ritueel is of een machine. Wie die criteria herkent, herkent het huis.',
                        'en' => 'We select clubs the way we select materials. Not on turnover. On atmosphere, on how the coffee is made, on whether the court is a ritual or a machine. Whoever recognises those criteria recognises the house.',
                        'fr' => 'Nous choisissons les clubs comme les matières. Pas sur le chiffre. Sur l’atmosphère, sur le rituel du court.',
                    ],
                    [
                        'nl' => 'Europa krijgt een netwerk van kamers. Elk Club Corner spreekt dezelfde taal — chocolade, goud, crème — zodat een lid in Antwerpen en een lid in Lissabon hetzelfde huis herkent. Dat is distributie als architectuur.',
                        'en' => 'Europe will have a network of rooms. Every Club Corner speaks the same language — chocolate, gold, cream — so a member in Antwerp and a member in Lisbon recognise the same house. That is distribution as architecture.',
                        'fr' => 'L’Europe aura un réseau de pièces. Chaque Club Corner parle la même langue, pour qu’Anvers et Lisbonne reconnaissent la même maison.',
                    ],
                ],
            ],
            [
                'slug' => 'kleding-die-fluistert',
                'asset' => 'room-dressing',
                'category' => $design,
                'title' => [
                    'nl' => 'De Kleedkamer — kleding die fluistert, op en naast de baan',
                    'en' => 'The Dressing Room — clothing that whispers, on and off the court',
                    'fr' => 'Le Dressing — des vêtements qui murmurent, sur le court et hors du court',
                ],
                'excerpt' => [
                    'nl' => 'Geen neon. Geen seizoenstrend. Activewear die dezelfde filosofie draagt als Heritage No.001: materialen eerst, logo\'s nooit.',
                    'en' => 'No neon. No seasonal trend. Activewear that carries the same philosophy as Heritage No.001: materials first, logos never.',
                    'fr' => 'Pas de néon. Pas de tendance. Une activewear qui porte la même philosophie que Heritage No.001.',
                ],
                'author' => $house,
                'date' => ['nl' => 'November 2025', 'en' => 'November 2025', 'fr' => 'Novembre 2025'],
                'body' => [
                    [
                        'nl' => 'De Kleedkamer is de derde kamer die het huis uitbreidt voorbij het racket. Polo, short, jack, pet, handdoek, grip — zes stukken, kleine oplage, het palet van het huis. Wie Heritage No.001 vasthoudt, moet de kleding herkennen.',
                        'en' => 'The Dressing Room is the third room that extends the house beyond the racket. Polo, short, jacket, cap, towel, grip — six pieces, small run, the palette of the house. Whoever holds Heritage No.001 should recognise the clothes.',
                        'fr' => 'Le Dressing prolonge la maison au-delà de la raquette. Six pièces, petite série, la palette de la maison.',
                    ],
                    [
                        'nl' => 'Technische stoffen uit Italiaanse mills, gesneden om te presteren en om ernaast te bestaan. Dat is de belofte: op de baan geen concessie, ernaast geen schreeuw. Founding Circle leden zien de collectie veertigacht uur eerder.',
                        'en' => 'Technical cloth from Italian mills, cut to perform and to exist beside the court. That is the promise: no concession on court, no shout off it. Founding Circle members see the collection forty-eight hours earlier.',
                        'fr' => 'Des tissus techniques italiens, coupés pour jouer et pour exister ensuite. Les membres du Founding Circle voient la collection quarante-huit heures plus tôt.',
                    ],
                    [
                        'nl' => 'De collectie wordt op dit moment ontworpen. Zij verschijnt met de levering van Heritage No.001. Tot die tijd fluistert de Kleedkamer alleen in het Journal — en in de kamers van het huis.',
                        'en' => 'The collection is being designed now. It appears with the delivery of Heritage No.001. Until then the Dressing Room whispers only in the Journal — and in the rooms of the house.',
                        'fr' => 'La collection s’écrit maintenant. Elle paraîtra avec Heritage No.001. En attendant, le Dressing ne murmure que dans le Journal.',
                    ],
                ],
            ],
            [
                'slug' => 'schelde-haven-huis',
                'asset' => 'antwerp-cityscape',
                'category' => $heritage,
                'title' => [
                    'nl' => 'De Schelde, de haven, en waarom een huis hier hoort',
                    'en' => 'The Scheldt, the port, and why a house belongs here',
                    'fr' => 'L’Escaut, le port, et pourquoi une maison a sa place ici',
                ],
                'excerpt' => [
                    'nl' => 'Antwerpen is een rivierstad. Wat binnenkomt, wordt gemaakt, en gaat weer de wereld in. Maison Anversa volgt die stroom.',
                    'en' => 'Antwerp is a river city. What arrives is made, and goes out into the world again. Maison Anversa follows that current.',
                    'fr' => 'Anvers est une ville-fleuve. Ce qui arrive est façonné, puis repart. Maison Anversa suit ce courant.',
                ],
                'author' => $yusuf,
                'date' => ['nl' => 'Oktober 2025', 'en' => 'October 2025', 'fr' => 'Octobre 2025'],
                'body' => [
                    [
                        'nl' => 'De Schelde is geen decor. Zij is de reden dat Antwerpen bestaat: getijden, handel, het geduld van een stad die weet dat alles aankomt en weer vertrekt. Een erfgoedhuis dat hier wortelt, mag die traagheid niet verloochenen.',
                        'en' => 'The Scheldt is not a backdrop. It is why Antwerp exists: tides, trade, the patience of a city that knows everything arrives and leaves again. A heritage house rooted here must not disown that slowness.',
                        'fr' => 'L’Escaut n’est pas un décor. C’est la raison d’Anvers : les marées, le commerce, la patience d’une ville. Une maison d’héritage ne peut pas trahir cette lenteur.',
                    ],
                    [
                        'nl' => 'Leder uit Europa, carbon uit de meest veeleisende weave, assemblage met de hand. Daarna vertrekt het racket — genummerd — naar wie het reserveerde. De havenlogica zit in de Founding Edition: beperkt, geadresseerd, verzekerd.',
                        'en' => 'Leather from Europe, carbon from the most demanding weave, assembled by hand. Then the racket leaves — numbered — to whoever reserved it. Port logic sits inside the Founding Edition: limited, addressed, insured.',
                        'fr' => 'Cuir d’Europe, carbone exigeant, assemblage à la main. Puis la raquette part — numérotée — vers celui qui l’a réservée.',
                    ],
                    [
                        'nl' => 'Ik woon in deze stad omdat zij niet om aandacht vraagt. Maison Anversa mag hetzelfde doen. Wie de Schelde kent, herkent het huis. Wie het huis kent, mag de rivier leren lezen.',
                        'en' => 'I live in this city because it does not ask for attention. Maison Anversa may do the same. Whoever knows the Scheldt recognises the house. Whoever knows the house may learn to read the river.',
                        'fr' => 'Je vis dans cette ville parce qu’elle ne demande pas l’attention. Maison Anversa peut faire de même.',
                    ],
                ],
            ],
            [
                'slug' => 'monogram-dat-je-bijna-niet-ziet',
                'asset' => 'heritage-001-detail-gravure',
                'category' => $design,
                'title' => [
                    'nl' => 'Het monogram dat je bijna niet ziet',
                    'en' => 'The monogram you almost do not see',
                    'fr' => 'Le monogramme que l’on ne voit presque pas',
                ],
                'excerpt' => [
                    'nl' => 'Een M en een A, in elkaar geschoven. Geen borstlogo. Een zegel voor wie dichtbij komt.',
                    'en' => 'An M and an A, folded into each other. No chest logo. A seal for whoever comes close.',
                    'fr' => 'Un M et un A entrelacés. Pas de logo sur la poitrine. Un sceau pour qui s’approche.',
                ],
                'author' => $house,
                'date' => ['nl' => 'September 2025', 'en' => 'September 2025', 'fr' => 'Septembre 2025'],
                'body' => [
                    [
                        'nl' => 'Het monogram van Maison Anversa is geen merk dat de afstand tot de toeschouwer overbrugt. Het is een zegel: klein, gouden, bedoeld voor de hand die het racket vasthoudt en voor wie naast u op de baan staat.',
                        'en' => 'The Maison Anversa monogram is not a brand that bridges the distance to the spectator. It is a seal: small, gold, meant for the hand that holds the racket and for whoever stands beside you on court.',
                        'fr' => 'Le monogramme n’est pas une marque pour le spectateur. C’est un sceau : petit, doré, pour la main qui tient la raquette.',
                    ],
                    [
                        'nl' => 'Op Heritage No.001 zit de gravure waar u haar voelt, niet waar een camera haar zoekt. Op de polo is het borduurwerk crème-op-crème tot het licht het vindt. Wie het zoekt, vindt het. Wie het niet zoekt, ziet een schoon vlak.',
                        'en' => 'On Heritage No.001 the engraving sits where you feel it, not where a camera looks. On the polo the embroidery is cream-on-cream until the light finds it. Whoever looks, finds it. Whoever does not, sees a clean field.',
                        'fr' => 'Sur Heritage No.001, la gravure est là où on la sent. Sur le polo, la broderie est crème sur crème jusqu’à ce que la lumière la trouve.',
                    ],
                    [
                        'nl' => 'Dat is de afspraak van het huis: herkenning onder gelijken, geen reclame voor de tribune. Het monogram is een handdruk, geen vlag.',
                        'en' => 'That is the house’s agreement: recognition among equals, no advertising for the stands. The monogram is a handshake, not a flag.',
                        'fr' => 'Tel est le pacte de la maison : se reconnaître entre pairs. Le monogramme est une poignée de main, pas un drapeau.',
                    ],
                ],
            ],
            [
                'slug' => 'handwerk-in-een-tijd-van-machines',
                'asset' => 'atelier-workshop',
                'category' => $craft,
                'title' => [
                    'nl' => 'Handwerk in een tijd van machines',
                    'en' => 'Handwork in an age of machines',
                    'fr' => 'Le travail de la main à l’âge des machines',
                ],
                'excerpt' => [
                    'nl' => 'Niet alles wat traag is, is nostalgisch. Sommige dingen kunnen niet anders dan met de hand — en dat is hun waarde.',
                    'en' => 'Not everything slow is nostalgic. Some things cannot be done except by hand — and that is their worth.',
                    'fr' => 'Tout ce qui est lent n’est pas nostalgique. Certaines choses ne se font qu’à la main — et c’est leur prix.',
                ],
                'author' => $house,
                'date' => ['nl' => 'Augustus 2025', 'en' => 'August 2025', 'fr' => 'Août 2025'],
                'body' => [
                    [
                        'nl' => 'Een machine kan honderd identieke frames per uur. Een hand kan er een paar, en elk raam is een beslissing. Heritage No.001 kiest de tweede lijn: niet omdat wij machines wantrouwen, maar omdat een numbered edition geen identieke menigte mag zijn.',
                        'en' => 'A machine can make a hundred identical frames an hour. A hand can make a few, and each frame is a decision. Heritage No.001 chooses the second line: not because we distrust machines, but because a numbered edition must not be an identical crowd.',
                        'fr' => 'Une machine fait cent cadres identiques à l’heure. Une main en fait quelques-uns, et chaque cadre est une décision.',
                    ],
                    [
                        'nl' => 'De gravure, de numerotering, het leder op de grip: daar stopt de lijn en begint iemand. U koopt geen productiestroom. U koopt de uren van iemand die uw nummer heeft gezien.',
                        'en' => 'The engraving, the numbering, the leather on the grip: there the line stops and someone begins. You are not buying a production flow. You are buying the hours of someone who has seen your number.',
                        'fr' => 'La gravure, le numéro, le cuir du grip : la ligne s’arrête et quelqu’un commence. Vous n’achetez pas un flux. Vous achetez des heures.',
                    ],
                    [
                        'nl' => 'In 2026 is dat een stelling. Maison Anversa stelt haar: honderd stuks, daarna nooit meer. Handwerk is de enige manier om die belofte waar te maken zonder haar te verdunnen.',
                        'en' => 'In 2026 that is a position. Maison Anversa takes it: one hundred pieces, then never again. Handwork is the only way to keep that promise without diluting it.',
                        'fr' => 'En 2026, c’est une position. Cent pièces, puis plus jamais. La main est la seule façon de tenir cette promesse.',
                    ],
                ],
            ],
            [
                'slug' => 'van-de-court-naar-de-stad',
                'asset' => 'heritage-001-lifestyle-court',
                'category' => $sport,
                'title' => [
                    'nl' => 'Van de court naar de stad: hoe een racket een huis draagt',
                    'en' => 'From the court to the city: how a racket carries a house',
                    'fr' => 'Du court à la ville : comment une raquette porte une maison',
                ],
                'excerpt' => [
                    'nl' => 'Heritage No.001 is geen uitrusting die u achterlaat in de kleedkamer. Het is het voorwerp waarmee het huis de stad in loopt.',
                    'en' => 'Heritage No.001 is not kit you leave in the changing room. It is the object with which the house walks into the city.',
                    'fr' => 'Heritage No.001 n’est pas un équipement que l’on laisse au vestiaire. C’est l’objet avec lequel la maison entre en ville.',
                ],
                'author' => $house,
                'date' => ['nl' => 'Juli 2025', 'en' => 'July 2025', 'fr' => 'Juillet 2025'],
                'body' => [
                    [
                        'nl' => 'De meeste sportobjecten hebben twee levens: op de baan, en in een tas. Heritage No.001 is gemaakt voor een derde — over de schouder, in de foyer, op tafel na de set. Daarom het leder, daarom de gravure, daarom geen schreeuw.',
                        'en' => 'Most sports objects have two lives: on court, and in a bag. Heritage No.001 is made for a third — over the shoulder, in the foyer, on the table after the set. Hence the leather, the engraving, the refusal to shout.',
                        'fr' => 'La plupart des objets de sport ont deux vies. Heritage No.001 en a une troisième — sur l’épaule, dans le foyer, sur la table après le set.',
                    ],
                    [
                        'nl' => 'Een huis dat alleen op de baan bestaat, is een merk. Een huis dat de stad in mag, is architectuur. Maison Anversa bouwt het tweede. Het racket is de sleutel, niet de reclame.',
                        'en' => 'A house that exists only on court is a brand. A house allowed into the city is architecture. Maison Anversa builds the second. The racket is the key, not the advertisement.',
                        'fr' => 'Une maison qui n’existe que sur le court est une marque. Une maison qui entre en ville est une architecture. La raquette est la clé.',
                    ],
                    [
                        'nl' => 'Wie hem draagt, draagt Antwerpen mee zonder het te zeggen. Dat is de stilste vorm van herkomst: zichtbaar voor wie kijkt, onleesbaar voor wie haast heeft.',
                        'en' => 'Whoever carries it, carries Antwerp without saying so. That is the quietest form of origin: visible to whoever looks, unreadable to whoever is in a hurry.',
                        'fr' => 'Qui la porte, porte Anvers sans le dire. L’origine la plus calme : visible pour qui regarde, illisible pour qui se presse.',
                    ],
                ],
            ],
            [
                'slug' => 'wat-we-bewaren-wanneer-we-nummeren',
                'asset' => 'heritage-001-front',
                'category' => $heritage,
                'title' => [
                    'nl' => 'Wat we bewaren wanneer we iets nummeren',
                    'en' => 'What we keep when we number something',
                    'fr' => 'Ce que l’on conserve quand on numérote',
                ],
                'excerpt' => [
                    'nl' => 'Een nummer is geen schaarste-truc. Het is een belofte dat dit stuk een plaats heeft in een reeks die stopt.',
                    'en' => 'A number is not a scarcity trick. It is a promise that this piece has a place in a series that stops.',
                    'fr' => 'Un numéro n’est pas un artifice de rareté. C’est la promesse qu’une pièce a sa place dans une série qui s’arrête.',
                ],
                'author' => $yusuf,
                'date' => ['nl' => 'Juni 2025', 'en' => 'June 2025', 'fr' => 'Juin 2025'],
                'body' => [
                    [
                        'nl' => 'Nummeren is een daad van geheugen. U krijgt geen willekeurig object. U krijgt een plaats: 001 tot 100, daarna niets. De reeks is het verhaal. Zonder einde is er geen Founding Edition, alleen voorraad.',
                        'en' => 'To number is an act of memory. You are not given a random object. You are given a place: 001 to 100, then nothing. The series is the story. Without an end there is no Founding Edition, only stock.',
                        'fr' => 'Numéroter est un acte de mémoire. On ne reçoit pas un objet au hasard. On reçoit une place : 001 à 100, puis plus rien.',
                    ],
                    [
                        'nl' => 'Op het certificaat staat uw nummer naast de zegel. In het paspoort staat de plek in het verhaal. Dat is geen extra. Dat is waarom het huis bestaat: dingen die men bewaart, niet weggooit.',
                        'en' => 'On the certificate your number sits beside the seal. In the passport sits your place in the story. That is not an extra. That is why the house exists: things people keep, not throw away.',
                        'fr' => 'Sur le certificat, votre numéro côtoie le sceau. Ce n’est pas un supplément. C’est la raison d’être de la maison : des choses que l’on garde.',
                    ],
                    [
                        'nl' => 'Heritage is not what we inherit. It is what we choose to protect. Nummeren is die keuze zichtbaar maken — voor u, en voor wie na u het racket in handen krijgt.',
                        'en' => 'Heritage is not what we inherit. It is what we choose to protect. Numbering makes that choice visible — for you, and for whoever holds the racket after you.',
                        'fr' => 'Heritage is not what we inherit. It is what we choose to protect. Numéroter rend ce choix visible.',
                    ],
                ],
            ],
            [
                'slug' => 'eerste-honderd-permanente-gemeenschap',
                'asset' => 'hero-mansion',
                'category' => $circle,
                'title' => [
                    'nl' => 'De eerste honderd als permanente gemeenschap',
                    'en' => 'The first hundred as a permanent community',
                    'fr' => 'Les cent premiers comme communauté permanente',
                ],
                'excerpt' => [
                    'nl' => 'Niet een lijst van kopers. Een kring die blijft — sessies, het Journal, de Kleedkamer, het huis.',
                    'en' => 'Not a list of buyers. A circle that remains — sessions, the Journal, the Dressing Room, the house.',
                    'fr' => 'Pas une liste d’acheteurs. Un cercle qui demeure — les sessions, le Journal, le Dressing, la maison.',
                ],
                'author' => $house,
                'date' => ['nl' => 'Mei 2025', 'en' => 'May 2025', 'fr' => 'Mai 2025'],
                'body' => [
                    [
                        'nl' => 'Een limited edition zonder gemeenschap is een schap. De Founding Circle bestaat zodat de eerste honderd elkaar kennen: op de baan, in het Journal, bij de events die alleen voor hen zijn. Het nummer is de sleutel. De kring is het huis.',
                        'en' => 'A limited edition without a community is a shelf. The Founding Circle exists so the first hundred know each other: on court, in the Journal, at the events that are only for them. The number is the key. The circle is the house.',
                        'fr' => 'Une édition limitée sans communauté est une étagère. Le Founding Circle existe pour que les cent premiers se connaissent.',
                    ],
                    [
                        'nl' => 'Referral is geen groeihack. Het is hoe een huis zichzelf voorstelt: u nodigt iemand uit die het verschil al voelt. De Circle groeit via herkenning, niet via bereik.',
                        'en' => 'Referral is not a growth hack. It is how a house introduces itself: you invite someone who already feels the difference. The Circle grows by recognition, not by reach.',
                        'fr' => 'La recommandation n’est pas une astuce de croissance. La maison se présente ainsi : vous invitez quelqu’un qui sent déjà la différence.',
                    ],
                    [
                        'nl' => 'Na 100 stopt de editie. De Circle stopt niet. Dat onderscheid is het hele punt. Product is eindig. Gemeenschap is hetgeen wij bouwen om te blijven.',
                        'en' => 'After 100 the edition stops. The Circle does not. That distinction is the entire point. Product is finite. Community is what we build to remain.',
                        'fr' => 'Après 100, l’édition s’arrête. Le Cercle non. Le produit est fini. La communauté est ce que nous bâtissons pour durer.',
                    ],
                ],
            ],
            [
                'slug' => 'italiaanse-mills-herkomst',
                'asset' => 'room-atelier',
                'category' => $materials,
                'title' => [
                    'nl' => 'Italiaanse mills en waarom herkomst telt',
                    'en' => 'Italian mills and why origin counts',
                    'fr' => 'Les mills italiens et pourquoi l’origine compte',
                ],
                'excerpt' => [
                    'nl' => 'De Kleedkamer begint niet bij het patroon. Zij begint bij de weverij die het palet van het huis al begrijpt.',
                    'en' => 'The Dressing Room does not begin with the pattern. It begins with the mill that already understands the palette of the house.',
                    'fr' => 'Le Dressing ne commence pas par le patron. Il commence par le mill qui comprend déjà la palette de la maison.',
                ],
                'author' => $house,
                'date' => ['nl' => 'April 2025', 'en' => 'April 2025', 'fr' => 'Avril 2025'],
                'body' => [
                    [
                        'nl' => 'Wij zoeken geen stof die “technisch genoeg” is. Wij zoeken een mill die chocolade, goud en crème niet als seizoen ziet maar als taal. Italiaanse huizen die al generaties voor stilte werken, herkennen die vraag.',
                        'en' => 'We are not looking for cloth that is “technical enough”. We are looking for a mill that does not see chocolate, gold and cream as a season but as a language. Italian houses that have worked for silence for generations recognise that question.',
                        'fr' => 'Nous ne cherchons pas un tissu « assez technique ». Nous cherchons un mill pour qui chocolat, or et crème sont une langue, pas une saison.',
                    ],
                    [
                        'nl' => 'Herkomst is geen label op een hangtag. Het is de zekerheid dat het weefsel volgend jaar nog hetzelfde zwijgt. Quiet luxury zonder herkomst is styling. Met herkomst is het een huis.',
                        'en' => 'Origin is not a line on a hangtag. It is the certainty that the cloth will still fall silent next year. Quiet luxury without origin is styling. With origin it is a house.',
                        'fr' => 'L’origine n’est pas une ligne sur une étiquette. C’est la certitude que le tissu se taira encore l’an prochain.',
                    ],
                    [
                        'nl' => 'De eerste collectie is klein omdat de mills klein werken. Dat is geen tekort. Dat is de maat van Maison Anversa: niet meer, niet minder, hetzelfde aantal als het racket verdraagt.',
                        'en' => 'The first collection is small because the mills work small. That is not a shortage. That is the measure of Maison Anversa: no more, no less, the same count the racket can bear.',
                        'fr' => 'La première collection est petite parce que les mills travaillent petit. C’est la mesure de Maison Anversa.',
                    ],
                ],
            ],
            [
                'slug' => 'bruin-goud-creme-palet',
                'asset' => 'room-library',
                'category' => $design,
                'title' => [
                    'nl' => 'Bruin, goud, crème: een palet dat niet verjaart',
                    'en' => 'Brown, gold, cream: a palette that does not date',
                    'fr' => 'Brun, or, crème : une palette qui ne se démode pas',
                ],
                'excerpt' => [
                    'nl' => 'Vier kleuren. Geen seizoen. Dezelfde taal voor racket, kleedkamer, journal en gevel.',
                    'en' => 'Four colours. No season. The same language for racket, dressing room, journal and façade.',
                    'fr' => 'Quatre couleurs. Pas de saison. La même langue pour la raquette, le dressing, le journal et la façade.',
                ],
                'author' => $house,
                'date' => ['nl' => 'Maart 2025', 'en' => 'March 2025', 'fr' => 'Mars 2025'],
                'body' => [
                    [
                        'nl' => 'Chocoladebruin is de grond. Antiek goud is het licht. Vintage crème is het papier. Zwart is de lijn. Samen zijn zij geen “collectiekleuren”. Zij zijn de gevel van het huis, in verf en in stof.',
                        'en' => 'Chocolate brown is the ground. Antique gold is the light. Vintage cream is the paper. Black is the line. Together they are not “collection colours”. They are the façade of the house, in paint and in cloth.',
                        'fr' => 'Le brun chocolat est le sol. L’or antique est la lumière. La crème vintage est le papier. Le noir est le trait.',
                    ],
                    [
                        'nl' => 'Een palet dat elk seizoen wisselt, is een kalender. Een palet dat blijft, is architectuur. Wie volgend jaar een andere kleur zoekt, zoekt een ander huis. Maison Anversa heeft er één.',
                        'en' => 'A palette that changes every season is a calendar. A palette that remains is architecture. Whoever looks for another colour next year is looking for another house. Maison Anversa has one.',
                        'fr' => 'Une palette qui change chaque saison est un calendrier. Une palette qui demeure est une architecture. Maison Anversa n’en a qu’une.',
                    ],
                    [
                        'nl' => 'U ziet het in de topbar, in de gravure, in de polo. Als het palet klopt, hoeft niemand het uit te leggen. Als het niet klopt, helpt geen copy. Daarom begint het huis bij kleur, niet bij slogan.',
                        'en' => 'You see it in the top bar, in the engraving, in the polo. If the palette is right, no one has to explain it. If it is not, no copy will help. That is why the house begins with colour, not with a slogan.',
                        'fr' => 'On le voit dans la barre, dans la gravure, dans le polo. Si la palette est juste, nul n’a besoin de l’expliquer.',
                    ],
                ],
            ],
            [
                'slug' => 'stilte-tussen-twee-punten',
                'asset' => 'heritage-001-lifestyle-court',
                'category' => $sport,
                'title' => [
                    'nl' => 'De stilte tussen twee punten',
                    'en' => 'The silence between two points',
                    'fr' => 'Le silence entre deux points',
                ],
                'excerpt' => [
                    'nl' => 'Padel wordt gewonnen in de seconde waarin niemand slaat. Een racket dat die seconde eert, is geen uitrusting. Het is een instrument.',
                    'en' => 'Padel is won in the second when no one hits. A racket that honours that second is not equipment. It is an instrument.',
                    'fr' => 'Le padel se gagne dans la seconde où personne ne frappe. Une raquette qui honore cette seconde n’est pas un équipement. C’est un instrument.',
                ],
                'author' => $yusuf,
                'date' => ['nl' => 'Februari 2025', 'en' => 'February 2025', 'fr' => 'Février 2025'],
                'body' => [
                    [
                        'nl' => 'Tussen de smash en de lob zit een stilte die de camera niet filmt. Daarin besluit u. Een racket met te veel gewicht in de kop vult die stilte met haast. Heritage No.001 is gebalanceerd om die seconde leeg te laten.',
                        'en' => 'Between the smash and the lob sits a silence the camera does not film. That is where you decide. A racket with too much weight in the head fills that silence with hurry. Heritage No.001 is balanced to leave that second empty.',
                        'fr' => 'Entre le smash et le lob, un silence que la caméra ne filme pas. Heritage No.001 est équilibré pour laisser cette seconde vide.',
                    ],
                    [
                        'nl' => 'Wij spreken over feel alsof het mystiek is. Het is geometrie, carbon, de grip in de palm. Quiet luxury op de baan is niet een kleur. Het is de afwezigheid van ruis in de hand.',
                        'en' => 'We speak of feel as if it were mystical. It is geometry, carbon, the grip in the palm. Quiet luxury on court is not a colour. It is the absence of noise in the hand.',
                        'fr' => 'On parle du feel comme d’un mystère. C’est de la géométrie, du carbone, le grip dans la paume. L’absence de bruit dans la main.',
                    ],
                    [
                        'nl' => 'Wie die stilte eenmaal gehoord heeft, gaat niet terug naar een racket dat schreeuwt. Dat is geen voorkeur. Dat is een drempel. Maison Anversa bouwt voor wie hem al over is.',
                        'en' => 'Whoever has heard that silence once does not go back to a racket that shouts. That is not a preference. It is a threshold. Maison Anversa builds for whoever has already crossed it.',
                        'fr' => 'Qui a entendu ce silence une fois ne revient pas à une raquette qui crie. Maison Anversa construit pour ceux qui ont déjà franchi ce seuil.',
                    ],
                ],
            ],
            [
                'slug' => 'est-2026-is-een-belofte',
                'asset' => 'maison-facade',
                'category' => $heritage,
                'title' => [
                    'nl' => 'Est. 2026 is geen marketing — het is een belofte',
                    'en' => 'Est. 2026 is not marketing — it is a promise',
                    'fr' => 'Est. 2026 n’est pas du marketing — c’est une promesse',
                ],
                'excerpt' => [
                    'nl' => 'Wij dateren het huis niet om oud te lijken. Wij dateren het om te zeggen: hier begint de reeks, en zij mag niet verwateren.',
                    'en' => 'We do not date the house to look old. We date it to say: the series begins here, and it must not dilute.',
                    'fr' => 'Nous ne datons pas la maison pour paraître ancienne. Nous la datons pour dire : la série commence ici, et elle ne doit pas se diluer.',
                ],
                'author' => $yusuf,
                'date' => ['nl' => 'Januari 2025', 'en' => 'January 2025', 'fr' => 'Janvier 2025'],
                'body' => [
                    [
                        'nl' => 'Est. 2026 staat op de gevel omdat het huis eerlijk is over zijn leeftijd. Wij pretenderen geen achttiende-eeuwse zolder. Wij pretenderen een begin: de Founding Edition, de eerste honderd, het eerste Journal.',
                        'en' => 'Est. 2026 sits on the façade because the house is honest about its age. We do not pretend an eighteenth-century attic. We pretend a beginning: the Founding Edition, the first hundred, the first Journal.',
                        'fr' => 'Est. 2026 est sur la façade parce que la maison est honnête sur son âge. Nous ne feignons pas un grenier du XVIIIe. Nous feignons un commencement.',
                    ],
                    [
                        'nl' => 'Een belofte vanaf dag één is zwaarder dan een mythe. U kunt ons erop afrekenen. Honderd stuks. Q1 2027. Leder, 3K, Antwerpen. Als wij dat niet halen, is Est. 2026 een datum van falen. Als wij het halen, is het de steen waarop de rest rust.',
                        'en' => 'A promise from day one is heavier than a myth. You can hold us to it. One hundred pieces. Q1 2027. Leather, 3K, Antwerp. If we miss it, Est. 2026 is a date of failure. If we keep it, it is the stone the rest sits on.',
                        'fr' => 'Une promesse dès le premier jour pèse plus qu’un mythe. Cent pièces. T1 2027. Cuir, 3K, Anvers. Si nous tenons, c’est la pierre du reste.',
                    ],
                    [
                        'nl' => 'Heritage is not what we inherit. It is what we choose to protect. In 2026 kiezen wij om te beginnen — klein, genummerd, en zonder haast. Dat is het huis. Dat is de belofte.',
                        'en' => 'Heritage is not what we inherit. It is what we choose to protect. In 2026 we choose to begin — small, numbered, and without hurry. That is the house. That is the promise.',
                        'fr' => 'Heritage is not what we inherit. It is what we choose to protect. En 2026, nous choisissons de commencer — petit, numéroté, sans hâte. Voilà la maison. Voilà la promesse.',
                    ],
                ],
            ],
        ];
    }
}
