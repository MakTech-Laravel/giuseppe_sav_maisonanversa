<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * The session notifications from the client brief (E1-E6), plus the removed
 * player's notice, the join-request and scoreboard notices, the organiser's
 * 48-hour free-places alert, the match card, and the three admin events. Every string is Dutch source copy and is resolved in
 * whatever locale is active when it is called, so callers switch to the
 * recipient's locale first.
 */
enum SessionNotice: string
{
    /** E1: a member joins, to the organiser. Kept for notifications already sent. */
    case NewPlayer = 'new_player';

    /** E2: the organiser approved the request, to that member. */
    case JoinConfirmed = 'join_confirmed';

    /** E3: the session became full, to every player including the organiser. */
    case SessionFull = 'session_full';

    /** E4: a player left, to the organiser (and the others if it was full). */
    case SpotOpened = 'spot_opened';

    /** E5: 24 hours before the start, to every player. */
    case Reminder = 'reminder';

    /** E6: the session was cancelled, to every player. */
    case Cancelled = 'cancelled';

    /** 48 hours before the start, free places remain, to the organiser. */
    case OpenSpotsReminder = 'open_spots_reminder';

    case Removed = 'removed';

    /** A member asked to join, to the organiser. */
    case JoinRequested = 'join_requested';

    /** The organiser turned the request down, to that member. */
    case RequestRejected = 'request_rejected';

    /** A member withdrew a pending request, to the organiser. */
    case RequestWithdrawn = 'request_withdrawn';

    /** A player submitted a score, to the organiser. */
    case ScoreSubmitted = 'score_submitted';

    /** The organiser approved a score, to every player. */
    case ScoreApproved = 'score_approved';

    /** The organiser turned a score down, to the player who submitted it. */
    case ScoreRejected = 'score_rejected';

    /** The organiser closed the session, to every player and the organiser. */
    case SessionFinished = 'session_finished';

    /** The session ended with 2+ players: the shareable match card is ready, to every player. */
    case MatchCardReady = 'match_card_ready';

    case AdminCreated = 'admin_created';

    case AdminFull = 'admin_full';

    case AdminCancelled = 'admin_cancelled';

    public function isForAdmins(): bool
    {
        return in_array($this, [self::AdminCreated, self::AdminFull, self::AdminCancelled], true);
    }

    public function subject(): string
    {
        return match ($this) {
            self::NewPlayer => __('Nieuwe speler voor uw sessie'),
            self::JoinConfirmed => __('U speelt mee'),
            self::SessionFull => __('Uw sessie is volzet'),
            self::SpotOpened => __('Een plaats is vrijgekomen'),
            self::Reminder => __('Morgen op de baan'),
            self::OpenSpotsReminder => __('Uw sessie heeft nog vrije plaatsen'),
            self::Cancelled, self::AdminCancelled => __('Sessie geannuleerd'),
            self::Removed => __('U bent uit een sessie verwijderd'),
            self::JoinRequested => __('Nieuwe aanvraag voor uw sessie'),
            self::RequestRejected => __('Uw aanvraag werd niet aanvaard'),
            self::RequestWithdrawn => __('Aanvraag ingetrokken'),
            self::ScoreSubmitted => __('Nieuwe score ingediend'),
            self::ScoreApproved => __('Score goedgekeurd'),
            self::ScoreRejected => __('Score niet aanvaard'),
            self::SessionFinished => __('Sessie afgesloten: de uitslag'),
            self::MatchCardReady => __('Uw wedstrijdkaart is klaar'),
            self::AdminCreated => __('Nieuwe sessie aangemaakt'),
            self::AdminFull => __('Sessie volzet'),
        };
    }

    /**
     * Opening line of the mail. `:name` is the member the notice is about,
     * `:club` the session's club.
     */
    public function intro(string $name, int $freePlaces = 0, string $club = ''): string
    {
        $replace = ['name' => $name, 'free' => $freePlaces, 'club' => $club];

        return match ($this) {
            self::NewPlayer => __(':name heeft zich aangemeld voor uw sessie.', $replace),
            self::JoinConfirmed => __('U bent ingeschreven. Hieronder vindt u alle details van de sessie.'),
            self::SessionFull => __('Alle plaatsen zijn ingenomen. Hieronder vindt u het volledige overzicht.'),
            self::SpotOpened => __(':name heeft de sessie verlaten. Deel de sessie opnieuw om de plaats in te vullen.', $replace),
            self::Reminder => __('Morgen staat u op de baan. Een korte herinnering:'),
            self::OpenSpotsReminder => __('Uw sessie heeft nog :free vrije plaatsen. Deel ze, of annuleer ze.', $replace),
            self::Cancelled, self::AdminCancelled => __('De onderstaande sessie is geannuleerd door :name.', $replace),
            self::Removed => __(':name heeft u verwijderd uit de onderstaande sessie.', $replace),
            self::JoinRequested => __(':name wil meespelen in uw sessie. Keur de aanvraag goed of weiger ze.', $replace),
            self::RequestRejected => __('De organisator heeft uw aanvraag voor de onderstaande sessie niet aanvaard.'),
            self::RequestWithdrawn => __(':name heeft de aanvraag voor uw sessie ingetrokken.', $replace),
            self::ScoreSubmitted => __(':name heeft een score ingediend voor uw sessie. Keur de score goed of weiger ze.', $replace),
            self::ScoreApproved => __('De organisator heeft de score van de onderstaande sessie goedgekeurd.'),
            self::ScoreRejected => __('De organisator heeft uw ingediende score niet aanvaard.'),
            self::SessionFinished => __('De sessie is afgesloten. Hieronder vindt u de officiële uitslag.'),
            self::MatchCardReady => __('Bedankt voor de sessie bij :club. Uw wedstrijdkaart staat klaar: deel ze met uw vrienden of bewaar ze als herinnering.', $replace),
            self::AdminCreated => __(':name heeft een nieuwe sessie aangemaakt.', $replace),
            self::AdminFull => __('Alle plaatsen van deze sessie zijn ingenomen.'),
        };
    }

    /**
     * Short text for the in-app bell. `:result` is the set line ("6-4, 6-3").
     */
    public function inAppMessage(string $name, string $when, int $freePlaces, string $result = '', string $club = ''): string
    {
        $replace = ['name' => $name, 'when' => $when, 'free' => $freePlaces, 'result' => $result, 'club' => $club];

        return match ($this) {
            self::NewPlayer => __(':name speelt mee in uw sessie van :when. Vrije plaatsen: :free.', $replace),
            self::JoinConfirmed => __('U bent ingeschreven voor de sessie van :when.', $replace),
            self::SessionFull, self::AdminFull => __('De sessie van :when is volzet.', $replace),
            self::SpotOpened => __(':name heeft de sessie van :when verlaten. Vrije plaatsen: :free.', $replace),
            self::Reminder => __('Herinnering: uw sessie begint op :when.', $replace),
            self::OpenSpotsReminder => __('Uw sessie van :when heeft nog :free vrije plaatsen. Deel ze, of annuleer ze.', $replace),
            self::Cancelled, self::AdminCancelled => __('De sessie van :when is geannuleerd door :name.', $replace),
            self::Removed => __(':name heeft u verwijderd uit de sessie van :when.', $replace),
            self::JoinRequested => __(':name wil meespelen in uw sessie van :when. Vrije plaatsen: :free.', $replace),
            self::RequestRejected => __('Uw aanvraag voor de sessie van :when werd niet aanvaard.', $replace),
            self::RequestWithdrawn => __(':name heeft de aanvraag voor uw sessie van :when ingetrokken.', $replace),
            self::ScoreSubmitted => __(':name heeft een score ingediend voor de sessie van :when.', $replace),
            self::ScoreApproved => __('De score van de sessie van :when is goedgekeurd: :result.', $replace),
            self::ScoreRejected => __('Uw score voor de sessie van :when werd niet aanvaard.', $replace),
            self::SessionFinished => __('De sessie van :when is afgesloten. Uitslag: :result.', $replace),
            self::MatchCardReady => __('Uw wedstrijdkaart van :club is klaar.', $replace),
            self::AdminCreated => __(':name heeft een nieuwe sessie aangemaakt voor :when.', $replace),
        };
    }

    public function ctaLabel(): string
    {
        return match ($this) {
            self::SpotOpened => __('Deel de sessie opnieuw'),
            self::OpenSpotsReminder => __('Deel de sessie'),
            self::Cancelled, self::Removed, self::RequestRejected => __('Bekijk andere sessies'),
            self::JoinRequested => __('Bekijk aanvraag'),
            self::ScoreSubmitted => __('Bekijk score'),
            self::ScoreApproved, self::SessionFinished => __('Bekijk uitslag'),
            self::MatchCardReady => __('Deel uw kaart'),
            default => __('Bekijk sessie'),
        };
    }

    /**
     * Which detail rows the mail shows, in order.
     *
     * @return list<'club'|'address'|'route'|'date'|'time'|'level'|'free'|'players'|'message'|'result'>
     */
    public function details(): array
    {
        return match ($this) {
            self::NewPlayer, self::JoinRequested => ['club', 'date', 'time', 'level', 'free'],
            self::JoinConfirmed => ['club', 'address', 'date', 'time', 'level', 'players'],
            self::SessionFull, self::AdminFull => ['club', 'address', 'route', 'date', 'time', 'level', 'players'],
            self::SpotOpened, self::RequestWithdrawn, self::OpenSpotsReminder => ['club', 'date', 'time', 'free'],
            self::Reminder => ['club', 'date', 'time', 'address', 'route'],
            self::Cancelled, self::AdminCancelled => ['club', 'date', 'time', 'level', 'message'],
            self::Removed, self::RequestRejected, self::ScoreSubmitted, self::ScoreRejected => ['club', 'date', 'time'],
            self::ScoreApproved, self::SessionFinished => ['club', 'date', 'time', 'result'],
            self::MatchCardReady => ['club', 'date', 'time'],
            self::AdminCreated => ['club', 'date', 'time', 'level', 'free'],
        };
    }

    /**
     * Notices about the result carry the official score in their text.
     */
    public function carriesResult(): bool
    {
        return $this === self::ScoreApproved || $this === self::SessionFinished;
    }

    public function attachesCalendar(): bool
    {
        return $this === self::JoinConfirmed || $this === self::SessionFull;
    }

    /**
     * The mail prints the plain session link so the organiser can forward it.
     */
    public function showsShareLink(): bool
    {
        return $this === self::OpenSpotsReminder;
    }

    /**
     * Second button next to the main call to action, if any.
     */
    public function secondaryCtaLabel(): ?string
    {
        return match ($this) {
            self::OpenSpotsReminder => __('Sessie annuleren'),
            self::MatchCardReady => __('Download'),
            default => null,
        };
    }

    /**
     * E2 for a late joiner: "today at 18:30" / "tomorrow at 18:30" when the
     * session starts within 24 hours of the moment the notice fires.
     */
    public function startsSoonLine(CarbonInterface $startsAt, ?CarbonInterface $now = null): ?string
    {
        if ($this !== self::JoinConfirmed) {
            return null;
        }

        $now ??= now();

        if ($startsAt->lessThanOrEqualTo($now) || $startsAt->greaterThan($now->copy()->addDay())) {
            return null;
        }

        $replace = ['time' => $startsAt->format('H:i')];

        return $startsAt->isSameDay($now)
            ? __('Uw sessie is vandaag om :time.', $replace)
            : __('Uw sessie is morgen om :time.', $replace);
    }
}
