<?php

namespace App\Mail;

use App\Enums\SessionMatchType;
use App\Enums\SessionNotice;
use App\Models\CommunitySession;
use App\Models\User;
use App\Support\BrandsMaisonMail;
use App\Support\MailLocale;
use App\Support\SessionCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One mail per session notice (E1-E6 and the admin events). The player list
 * and free places are captured when the notice fires, so a mail that waits
 * in the queue still describes the moment it is about.
 */
class SessionNoticeMail extends Mailable implements ShouldQueue
{
    use BrandsMaisonMail, Queueable, SerializesModels;

    /**
     * @param  list<string>  $players
     */
    public function __construct(
        public CommunitySession $session,
        public SessionNotice $notice,
        public string $subjectName,
        public array $players,
        public int $freePlaces,
        public string $sessionUrl,
        public string $ctaUrl,
        ?string $locale = null,
        public ?string $timingNote = null,
        public ?string $secondaryCtaUrl = null,
    ) {
        $this->locale(MailLocale::resolve($locale));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->notice->subject(),
        );
    }

    public function content(): Content
    {
        $this->session->loadMissing('club');
        $club = $this->session->club;

        return new Content(
            html: 'emails.session-notice',
            with: [
                ...$this->maisonBrandData($this->locale, withHomeCta: false),
                'title' => $this->notice->subject(),
                'intro' => $this->notice->intro($this->subjectName, $this->freePlaces, (string) $club?->name),
                'timingNote' => $this->timingNote,
                'shareUrl' => $this->notice->showsShareLink() ? $this->session->shareUrl($this->locale) : null,
                'secondaryCtaUrl' => $this->secondaryCtaUrl,
                'secondaryCtaLabel' => $this->secondaryCtaUrl !== null ? $this->notice->secondaryCtaLabel() : null,
                'rows' => $this->detailRows(),
                'players' => in_array('players', $this->notice->details(), true) ? $this->players : [],
                'organiserMessage' => in_array('message', $this->notice->details(), true)
                    ? $this->session->cancellation_message
                    : null,
                'routeUrl' => in_array('route', $this->notice->details(), true) && $club !== null
                    ? self::routeUrl($club->lat, $club->lng, $club->addressLine())
                    : null,
                'ctaUrl' => $this->ctaUrl,
                'ctaLabel' => $this->notice->ctaLabel(),
            ],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        if (! $this->notice->attachesCalendar()) {
            return [];
        }

        return [
            Attachment::fromData(
                fn (): string => SessionCalendar::make($this->session, $this->sessionUrl),
                'session.ics',
            )->withMime('text/calendar; charset=utf-8; method=PUBLISH'),
        ];
    }

    /**
     * Google Maps directions, by coordinates when the club has them.
     */
    public static function routeUrl(mixed $lat, mixed $lng, string $address): ?string
    {
        if (is_numeric($lat) && is_numeric($lng)) {
            return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode((float) $lat.','.(float) $lng);
        }

        return filled($address)
            ? 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($address)
            : null;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function detailRows(): array
    {
        $session = $this->session;
        $club = $session->club;
        $rows = [];

        foreach ($this->notice->details() as $detail) {
            if ($detail === 'result') {
                array_push($rows, ...$this->resultRows());

                continue;
            }

            $row = match ($detail) {
                'club' => $club ? [__('Club'), $club->name] : null,
                'address' => $club && filled($club->addressLine()) ? [__('Adres'), $club->addressLine()] : null,
                'date' => [__('Datum'), $session->starts_at->translatedFormat('l j F Y')],
                'time' => [
                    __('Tijd'),
                    $session->starts_at->format('H:i').($session->ends_at ? ' – '.$session->ends_at->format('H:i') : ''),
                ],
                'level' => $session->level ? [__('Niveau'), __($session->level->label())] : null,
                'free' => [__('Vrije plaatsen'), (string) $this->freePlaces],
                default => null,
            };

            if ($row !== null) {
                $rows[] = ['label' => $row[0], 'value' => $row[1]];
            }
        }

        return $rows;
    }

    /**
     * Match format, line-ups, set scores and the winner of the official result.
     *
     * @return list<array{label: string, value: string}>
     */
    private function resultRows(): array
    {
        $score = $this->session->officialScore()->first();

        if ($score === null) {
            return [];
        }

        $names = User::query()
            ->whereIn('id', [...$score->team_a, ...$score->team_b])
            ->pluck('name', 'id');

        $teamLine = fn (array $ids): string => collect($ids)
            ->map(fn (int|string $id): string => (string) ($names[(int) $id] ?? ''))
            ->filter()
            ->implode(' · ');

        $singles = $score->match_type === SessionMatchType::Singles;
        $sideA = $singles ? __('Speler A') : __('Team A');
        $sideB = $singles ? __('Speler B') : __('Team B');

        $winner = match ($score->winner()) {
            'a' => $sideA.' ('.$teamLine($score->team_a).')',
            'b' => $sideB.' ('.$teamLine($score->team_b).')',
            default => __('Gelijkspel'),
        };

        return [
            ['label' => __('Formaat'), 'value' => __($score->match_type->label()).' · '.__($score->format->label())],
            ['label' => $sideA, 'value' => $teamLine($score->team_a)],
            ['label' => $sideB, 'value' => $teamLine($score->team_b)],
            ['label' => __('Uitslag'), 'value' => $score->setsLine()],
            ['label' => __('Winnaar'), 'value' => $winner],
        ];
    }
}
