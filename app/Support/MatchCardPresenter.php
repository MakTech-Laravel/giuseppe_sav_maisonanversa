<?php

namespace App\Support;

use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Illuminate\Support\Traits\Localizable;

/**
 * Everything printed on a match card, in the language of whoever opens it.
 *
 * Players appear as initials plus their first name. When
 * `show_first_name_on_cards` is off, the first-name slot prints the
 * translated Private label instead. Last names never leave this class.
 *
 * @phpstan-type CardPlayer array{initials: string, first_name: string|null, edition: string|null}
 * @phpstan-type CardPayload array{
 *     format: string,
 *     locale: string,
 *     width: int,
 *     height: int,
 *     palette: array<string, string>,
 *     eyebrow: string,
 *     club: string,
 *     date_line: string,
 *     players: list<CardPlayer>,
 *     result: array{label: string, line: string}|null,
 *     closing: string,
 *     footer: string
 * }
 */
final class MatchCardPresenter
{
    use Localizable;

    public const FORMATS = ['story', 'post'];

    /**
     * @var array<string, array{width: int, height: int}>
     */
    private const SIZES = [
        'story' => ['width' => 1080, 'height' => 1920],
        'post' => ['width' => 1080, 'height' => 1350],
    ];

    /**
     * Brief section 04: Story is Deep Chocolate, Post is Warm Ivory.
     *
     * @var array<string, array<string, string>>
     */
    private const PALETTES = [
        'story' => [
            'background' => '#291C18',
            'highlight' => '#3E2C23',
            'text' => '#F3EBE3',
            'eyebrow' => '#CCB498',
            'footer' => '#BBA995',
            'closing' => '#D6BE92',
            'gold' => '#B2915C',
            'frame' => '',
        ],
        'post' => [
            'background' => '#F3EBE3',
            'highlight' => '#F3EBE3',
            'text' => '#291C18',
            'eyebrow' => '#7A5C2E',
            'footer' => '#7A5C2E',
            'closing' => '#7A5C2E',
            'gold' => '#B2915C',
            'frame' => '#CDBFAF',
        ],
    ];

    /**
     * Date line per locale: "Dinsdag 6 oktober · 12.30", "Mardi 6 octobre · 12h30".
     *
     * @var array<string, string>
     */
    private const DATE_FORMATS = [
        'nl' => 'l j F · H.i',
        'en' => 'l j F · H.i',
        'fr' => 'l j F · H\hi',
    ];

    public function __construct(private PassportPresenter $passports) {}

    /**
     * @return CardPayload
     */
    public function payload(CommunitySession $session, string $locale, string $format): array
    {
        $locale = MailLocale::resolve($locale);

        return $this->withLocale($locale, function () use ($session, $locale, $format): array {
            $session->loadMissing(['club', 'participants.user', 'officialScore']);
            $score = $session->officialScore;

            return [
                'format' => $format,
                'locale' => $locale,
                ...self::SIZES[$format],
                'palette' => self::PALETTES[$format],
                'eyebrow' => __('Sessie').' · '.$session->sport->label(),
                'club' => (string) ($session->club?->name ?? __('Sessie')),
                'date_line' => $this->dateLine($session, $locale),
                'players' => $this->players($session),
                'result' => $score !== null
                    ? ['label' => __('Uitslag'), 'line' => $score->setsLine()]
                    : null,
                'closing' => __('Na de koffie, de baan.'),
                'footer' => __('maisonanversa.com · Antwerpen'),
            ];
        });
    }

    /**
     * Confirmed players, organiser first. Pass `$locale` so the Private label
     * (and any other translated copy) follows the URL language, not just the
     * ambient app locale.
     *
     * @return list<CardPlayer>
     */
    public function players(CommunitySession $session, ?string $locale = null): array
    {
        $build = function () use ($session): array {
            $session->loadMissing('participants.user');

            return $session->participants
                ->sortBy(fn (CommunitySessionParticipant $row): array => [
                    $row->user_id === $session->host_id ? 0 : 1,
                    $row->created_at?->getTimestamp() ?? 0,
                ])
                ->map(fn (CommunitySessionParticipant $row): array => $this->player($row->user))
                ->values()
                ->all();
        };

        if ($locale === null) {
            return $build();
        }

        return $this->withLocale(MailLocale::resolve($locale), $build);
    }

    /**
     * @return CardPlayer
     */
    public function player(User $user): array
    {
        $given = PersonName::given($user);
        $family = PersonName::family($user);
        $initials = mb_strtoupper(mb_substr($given, 0, 1).mb_substr($family, 0, 1));

        return [
            'initials' => $initials !== '' ? $initials : mb_strtoupper(mb_substr((string) $user->name, 0, 1)),
            'first_name' => $user->show_first_name_on_cards
                ? ($given !== '' ? $given : null)
                : __('Privé'),
            'edition' => $this->edition($user),
        ];
    }

    /**
     * Stable cache key: any change to what is printed produces a new PNG.
     *
     * @param  CardPayload  $payload
     */
    public function hash(array $payload): string
    {
        return substr(sha1(json_encode($payload).'|v'.config('maison.match_cards.template_version')), 0, 16);
    }

    /**
     * URLs the share UI needs. Every one is public: the share token is the key.
     *
     * @return array{
     *     share_url: string,
     *     story_url: string,
     *     post_url: string,
     *     download_story_url: string,
     *     download_post_url: string,
     *     has_result: bool
     * }
     */
    public function links(CommunitySession $session, string $locale): array
    {
        $image = fn (string $format, bool $download = false): string => route('maison.sessions.card', array_filter([
            'locale' => $locale,
            'shareToken' => $session->share_token,
            'format' => $format,
            'download' => $download ? 1 : null,
        ]));

        return [
            'share_url' => $session->shareUrl($locale),
            'story_url' => $image('story'),
            'post_url' => $image('post'),
            'download_story_url' => $image('story', true),
            'download_post_url' => $image('post', true),
            'has_result' => $session->officialScore()->exists(),
        ];
    }

    private function dateLine(CommunitySession $session, string $locale): string
    {
        $line = $session->starts_at->copy()
            ->locale($locale)
            ->translatedFormat(self::DATE_FORMATS[$locale] ?? self::DATE_FORMATS['en']);

        return mb_strtoupper(mb_substr($line, 0, 1)).mb_substr($line, 1);
    }

    /**
     * "No. 012" for Heritage No.001 owners (order first, then an approved claim).
     */
    private function edition(User $user): ?string
    {
        if (! $user->isFoundingCircle()) {
            return null;
        }

        $order = $this->passports->heritageOrder($user);

        if ($order !== null && $order->edition_number !== null) {
            return 'No. '.str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT);
        }

        $claim = $this->passports->approvedClaim($user);

        return $claim !== null ? 'No. '.$claim->paddedEditionNumber() : null;
    }
}
