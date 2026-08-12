<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Demo Community feed catalog. Posts are static (Dutch source copy) and
 * paginated for Inertia InfiniteScroll — no database table in this phase.
 *
 * @phpstan-type FeedComment array{id: string, name: string, initials: string, body: string, info: string}
 * @phpstan-type FeedPost array{
 *     id: string,
 *     official?: bool,
 *     initials?: string,
 *     avatarBg?: string,
 *     name: string,
 *     info: string,
 *     badge: string,
 *     badgeOfficial?: bool,
 *     content: string,
 *     imageLabel?: string,
 *     likes: int,
 *     comments: list<FeedComment>
 * }
 */
final class CommunityFeed
{
    public const PER_PAGE = 8;

    /**
     * @return LengthAwarePaginator<int, FeedPost>
     */
    public static function paginate(Request $request, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $posts = self::posts();
        $total = count($posts);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, $request->integer('page', 1));

        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $locale = $request->route('locale');
        $pathLocale = is_string($locale) ? $locale : app()->getLocale();

        $slice = array_values(array_slice($posts, ($page - 1) * $perPage, $perPage));

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => route('maison.community', ['locale' => $pathLocale]),
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @return list<FeedPost>
     */
    public static function posts(): array
    {
        return self::catalog();
    }

    /**
     * @return list<FeedPost>
     */
    private static function catalog(): array
    {
        $base = self::seedPosts();
        $extras = self::generatedPosts();

        return [...$base, ...$extras];
    }

    /**
     * Hand-crafted seed posts with richer comment threads.
     *
     * @return list<FeedPost>
     */
    private static function seedPosts(): array
    {
        return [
            [
                'id' => 'official-1',
                'official' => true,
                'name' => 'Maison Anversa',
                'info' => 'Officieel · 1 dag geleden · Antwerpen',
                'badge' => 'Officieel',
                'badgeOfficial' => true,
                'content' => "Productie update Heritage No.001 — De eerste batch frames zijn afgewerkt. De lederen grepen worden deze week aangebracht. De planning voor Q1 2027 ligt volledig op schema.\n\nWe houden u op de hoogte via de Heritage Letter en hier in de Community.",
                'likes' => 67,
                'comments' => [
                    self::comment('c-o1-1', 'Thomas Janssen', 'TJ', 'Fantastisch nieuws. Kan niet wachten op de grepen.', 'Nr. 007 · 20u geleden'),
                    self::comment('c-o1-2', 'Amelie Verschueren', 'AV', 'De planning ziet er strak uit. Respect.', 'Nr. 023 · 18u geleden'),
                    self::comment('c-o1-3', 'Marc Kessels', 'MK', 'Q1 2027 — genoteerd in de agenda.', 'Nr. 041 · 12u geleden'),
                    self::comment('c-o1-4', 'Lisa Berg', 'LB', 'Heritage Letter stond ook al klaar. Dank!', 'Nr. 058 · 8u geleden'),
                ],
            ],
            [
                'id' => 'post-tj',
                'initials' => 'TJ',
                'avatarBg' => '#2A1810',
                'name' => 'Thomas Janssen',
                'info' => 'Nr. 007 · Padel Club Antwerpen · 2 uur geleden',
                'badge' => 'FC Lid',
                'content' => "Eerste sessie vandaag met Heritage No.001. Het verschil in gevoel met een standaard racket is onmiddellijk merkbaar. De lederen greep in het bijzonder — het warmt op in je hand en voelt na een uur aan alsof het altijd van jou is geweest.\n\nNummer 007. Trots lid van de Founding Circle.",
                'imageLabel' => 'Padel Court Session',
                'likes' => 12,
                'comments' => [
                    self::comment('c-tj-1', 'Amelie Verschueren', 'AV', 'Helemaal mee eens over die greep.', 'Nr. 023 · 1u geleden'),
                    self::comment('c-tj-2', 'Pieter De Vos', 'PD', 'Welk niveau speelde je vandaag?', 'Nr. 012 · 45m geleden'),
                    self::comment('c-tj-3', 'Maison Anversa', 'MA', 'Mooi om te horen, Thomas. Welkom in de Circle.', 'Officieel · 30m geleden'),
                ],
            ],
            [
                'id' => 'post-av',
                'initials' => 'AV',
                'avatarBg' => '#1A2010',
                'name' => 'Amelie Verschueren',
                'info' => 'Nr. 023 · Padel One Brussels · 5 uur geleden',
                'badge' => 'FC Lid',
                'content' => "De Heritage doos is aangekomen. De unboxing is een ervaring op zich. De welkomstkaart, het paspoort, het certificaat — alles ademt kwaliteit. Het racket heb ik nog niet gespeeld maar ik ben al verliefd.\n\nDit is wat luxe aanvoelt.",
                'likes' => 28,
                'comments' => [
                    self::comment('c-av-1', 'Lisa Berg', 'LB', 'De doos alleen al is Instagram-waardig.', 'Nr. 058 · 3u geleden'),
                    self::comment('c-av-2', 'Thomas Janssen', 'TJ', 'Wacht tot je speelt. Nog een niveau hoger.', 'Nr. 007 · 2u geleden'),
                    self::comment('c-av-3', 'Karel Vermeulen', 'KV', 'Proficiat met Nr. 023!', 'Nr. 019 · 1u geleden'),
                    self::comment('c-av-4', 'Sofie Maes', 'SM', 'Zelfde gevoel hier. Welkom.', 'Nr. 031 · 40m geleden'),
                ],
            ],
            [
                'id' => 'post-mk',
                'initials' => 'MK',
                'avatarBg' => '#201520',
                'name' => 'Marc Kessels',
                'info' => 'Nr. 041 · Padel Club Rotterdam · Gisteren',
                'badge' => 'FC Lid',
                'content' => 'Wie speelt er volgende zaterdag in Amsterdam? Ik zoek nog twee spelers voor een 4-set sessie. Niveau intermediair tot gevorderd. DM me of reageer hieronder.',
                'likes' => 5,
                'comments' => [
                    self::comment('c-mk-1', 'Lisa Berg', 'LB', 'Ik kan zaterdag ochtend. Amsterdam Padel Club?', 'Nr. 058 · 20u geleden'),
                    self::comment('c-mk-2', 'Pieter De Vos', 'PD', 'Tel mij erbij als er nog plek is.', 'Nr. 012 · 18u geleden'),
                    self::comment('c-mk-3', 'Marc Kessels', 'MK', 'Perfect — stuur me jullie nummers.', 'Nr. 041 · 16u geleden'),
                    self::comment('c-mk-4', 'Amelie Verschueren', 'AV', 'Jammer, ik speel in Brussel die dag.', 'Nr. 023 · 14u geleden'),
                    self::comment('c-mk-5', 'Thomas Janssen', 'TJ', 'Volgende week Antwerpen dan?', 'Nr. 007 · 12u geleden'),
                ],
            ],
            [
                'id' => 'official-2',
                'official' => true,
                'name' => 'Maison Anversa',
                'info' => 'Officieel · 3 dagen geleden · Antwerpen',
                'badge' => 'Officieel',
                'badgeOfficial' => true,
                'content' => "Club Corner partners: de demo-rackets voor januari staan klaar voor verzending. Bevestig je voorraad via de partnerportal of mail partners@maisonanversa.com.\n\nLeden kunnen vanaf volgende week een demo boeken bij Padel Club Antwerpen en Padel One Brussels.",
                'likes' => 41,
                'comments' => [
                    self::comment('c-o2-1', 'Karel Vermeulen', 'KV', 'Antwerpen is geboekt. Dank!', 'Nr. 019 · 2d geleden'),
                    self::comment('c-o2-2', 'Sofie Maes', 'SM', 'Brussel ook — fijne service.', 'Nr. 031 · 2d geleden'),
                ],
            ],
            [
                'id' => 'post-lb',
                'initials' => 'LB',
                'avatarBg' => '#1C1824',
                'name' => 'Lisa Berg',
                'info' => 'Nr. 058 · Amsterdam Padel Club · 8 uur geleden',
                'badge' => 'FC Lid',
                'content' => "Eerste Founding Circle ochtend in Amsterdam. Koude hallen, warme gesprekken. Iedereen droeg het paspoort bij zich alsof het een ticket naar iets groters was.\n\nDit is community zoals het hoort.",
                'imageLabel' => 'Founding Circle Morning',
                'likes' => 19,
                'comments' => [
                    self::comment('c-lb-1', 'Marc Kessels', 'MK', 'Was erbij. Top organisatie.', 'Nr. 041 · 6u geleden'),
                    self::comment('c-lb-2', 'Amelie Verschueren', 'AV', 'Volgende keer Brussel?', 'Nr. 023 · 5u geleden'),
                    self::comment('c-lb-3', 'Maison Anversa', 'MA', 'Mooi verslag, Lisa. Tot in maart.', 'Officieel · 4u geleden'),
                ],
            ],
            [
                'id' => 'post-pd',
                'initials' => 'PD',
                'avatarBg' => '#241810',
                'name' => 'Pieter De Vos',
                'info' => 'Nr. 012 · Club Corner Partner · 1 dag geleden',
                'badge' => 'Partner',
                'content' => 'Onze leden vroegen al weken naar Heritage. De eerste demo-ochtend was binnen een uur volgeboekt. Wie nog een slot wil: mail de club — we zetten een tweede sessie open.',
                'likes' => 22,
                'comments' => [
                    self::comment('c-pd-1', 'Thomas Janssen', 'TJ', 'Sterk werk van de club.', 'Nr. 007 · 22u geleden'),
                    self::comment('c-pd-2', 'Sofie Maes', 'SM', 'Ik sta op de wachtlijst!', 'Nr. 031 · 20u geleden'),
                ],
            ],
            [
                'id' => 'post-sm',
                'initials' => 'SM',
                'avatarBg' => '#182018',
                'name' => 'Sofie Maes',
                'info' => 'Nr. 031 · Padel One Brussels · 2 dagen geleden',
                'badge' => 'FC Lid',
                'content' => "Het certificaat hangt naast mijn rackethouder. Elke keer dat ik de deur van de kleedkamer opendoe, herinnert het me waarom ik Nr. 031 koos.\n\nNiet voor de status — voor het gevoel van ergens bij te horen.",
                'likes' => 33,
                'comments' => [
                    self::comment('c-sm-1', 'Amelie Verschueren', 'AV', 'Prachtig gezegd.', 'Nr. 023 · 1d geleden'),
                    self::comment('c-sm-2', 'Lisa Berg', 'LB', 'Zelfde vibe hier met Nr. 058.', 'Nr. 058 · 1d geleden'),
                    self::comment('c-sm-3', 'Karel Vermeulen', 'KV', 'Founding Circle in één zin.', 'Nr. 019 · 20u geleden'),
                ],
            ],
        ];
    }

    /**
     * Additional posts so InfiniteScroll has multiple pages (~40 total).
     *
     * @return list<FeedPost>
     */
    private static function generatedPosts(): array
    {
        $authors = [
            ['KV', 'Karel Vermeulen', '#201818', 'Nr. 019 · Padel Club Antwerpen'],
            ['YS', 'Yusuf Savran', '#291c18', 'Nr. 001 · Maison Anversa'],
            ['ND', 'Nora Dupont', '#1a2420', 'Nr. 064 · Padel One Brussels'],
            ['JH', 'Jonas Hendriks', '#241c14', 'Nr. 077 · Amsterdam Padel Club'],
            ['EL', 'Emma Lauwers', '#1c1a24', 'Nr. 082 · Padel Rotterdam'],
            ['RB', 'Ruben Bakker', '#221810', 'Nr. 045 · Club Corner Partner'],
            ['CT', 'Chloe Teirlinck', '#18221c', 'Nr. 053 · Padel Club Antwerpen'],
            ['FW', 'Felix Wouters', '#201410', 'Nr. 088 · Padel One Brussels'],
        ];

        $bodies = [
            'Avondsessie onder de lights. Het racket voelde sneller in de smash, stabieler in de volley. Wie zegt dat craftsmanship geen verschil maakt, heeft Nr.001 nog niet vastgehad.',
            'Kleine tip: bewaar je paspoort in de doos tot de eerste clubavond. Het ritueel van openen met anderen maakt het waardevoller.',
            'Op zoek naar een vaste speelmaat in de regio Brussel — intermediair, weekavonden. Reageer gerust.',
            'De lederen greep vraagt een week om “van jou” te worden. Daarna wil je niets anders meer.',
            'Club Corner update: we hangen de Heritage Letter poster in de lounge. Leden vragen ernaar bij de receptie.',
            'Eerste toernooi met alleen Founding Circle racketten. De geluiden op de court waren… anders. Stilller, scherper.',
            'Wie was er bij de Behind the Scenes stream? De details over de carbon layering waren goud waard.',
            'Reisde vandaag met Nr.001 in de cabin. De sleeve past perfect naast de laptop. Detailwerk.',
            'Nieuwe bal, oud ritueel: drie drops, één ademteug, spelen. Heritage verandert het ritueel niet — het verfijnt het.',
            'Amsterdam vraagt om meer sessies. Ik zet er één klaar voor begin februari. Reacties = interesse.',
            'Partner hier: onze juniors mogen de demo niet aanraken. Alleen Founding Members. Ze kijken. Dat is de magie.',
            'Het serienummer op mijn frame is bijna een handtekening. Ik lees het soms hardop voor ik ga spelen.',
            'Cold start in Rotterdam. Handschoenen uit, greep in. Warmte binnen twee games.',
            'Community tip: tag je club in posts. Zo vinden nieuwe leden sneller een court dichtbij.',
            'Vandaag de certificate wall gezien bij een partnerclub. Dertig nummers. Voelde als familie.',
            'Wie heeft tips voor reizen met het racket in de trein? Ik neem de sleeve, maar zoek een betere strap.',
            'Evening lights + Heritage No.001 = mijn favoriete combinatie deze winter.',
            'De Journal piece over craftsmanship raakte me. Daarom speel ik trager — met intentie.',
            'Zoek nog één speler voor zondag 11:00 in Antwerpen. Niveau open. Koffie na.',
            'Off-topic: de cream doos maakt een mooi cadeau-moment. Gaf er één door aan mijn coach. Tranen.',
            'Eerste smash met vertrouwen. Het frame antwoordt. Dat is het enige wat ik kan zeggen.',
            'Founding Circle is geen status. Het is een belofte om zorgvuldig te spelen — en te delen.',
            'Brussel-sessie vol. Wachtlijst open. We openen een tweede court als we 4 extra hebben.',
            'Partnervraag: mogen we de Heritage Letter QR in de kleedkamer hangen? Leden scannen al.',
            'Nummer 088 checking in. Lang gewacht. Waard.',
            'Kleine victorie: eindelijk de juiste spanning op de snaren voor mijn smash. Advies van Nr.007 hielp.',
            'Wie komt naar het Launch Event in maart? Ik plan reis + hotel alvast.',
            'Na de sessie bleven we natafelen. Community is dat — niet alleen de score.',
            'Het paspoort stempelen bij elke club is mijn nieuwe hobby. Drie stempels tot nu.',
            'Quiet luxury op de court. Geen logoscreaming. Alleen materiaal dat spreekt.',
            'Vraag aan de Circle: string tension voor double sessions? Ik zit op 24 kg.',
            'Tot ziens op de feed. Tot snel op de court.',
        ];

        $posts = [];

        foreach ($bodies as $index => $body) {
            $author = $authors[$index % count($authors)];
            $day = ($index % 5) + 1;
            $likes = 3 + (($index * 7) % 40);
            $commentCount = 2 + ($index % 4);

            $comments = [];
            for ($c = 0; $c < $commentCount; $c++) {
                $replyAuthor = $authors[($index + $c + 1) % count($authors)];
                $comments[] = self::comment(
                    "gen-{$index}-c{$c}",
                    $replyAuthor[1],
                    $replyAuthor[0],
                    match ($c % 3) {
                        0 => 'Helemaal mee eens.',
                        1 => 'Sterk verwoord — tot op de court.',
                        default => 'Ik reageer later met details.',
                    },
                    $replyAuthor[3].' · '.($c + 1).'u geleden',
                );
            }

            $posts[] = [
                'id' => 'gen-'.$index,
                'initials' => $author[0],
                'avatarBg' => $author[2],
                'name' => $author[1],
                'info' => $author[3].' · '.$day.'d geleden',
                'badge' => str_contains($author[3], 'Partner') ? 'Partner' : 'FC Lid',
                'content' => $body,
                'likes' => $likes,
                'comments' => $comments,
            ];
        }

        return $posts;
    }

    /**
     * @return FeedComment
     */
    private static function comment(
        string $id,
        string $name,
        string $initials,
        string $body,
        string $info,
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'initials' => $initials,
            'body' => $body,
            'info' => $info,
        ];
    }
}
