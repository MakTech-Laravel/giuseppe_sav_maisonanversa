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
            self::NewPlayer => __('New player for your session'),
            self::JoinConfirmed => __('You\'re in'),
            self::SessionFull => __('Your session is full'),
            self::SpotOpened => __('A spot has opened up'),
            self::Reminder => __('Tomorrow on the court'),
            self::OpenSpotsReminder => __('Your session still has free places'),
            self::Cancelled, self::AdminCancelled => __('Session cancelled'),
            self::Removed => __('You have been removed from a session'),
            self::JoinRequested => __('New request for your session'),
            self::RequestRejected => __('Your request was not accepted'),
            self::RequestWithdrawn => __('Request withdrawn'),
            self::ScoreSubmitted => __('New score submitted'),
            self::ScoreApproved => __('Score approved'),
            self::ScoreRejected => __('Score not accepted'),
            self::SessionFinished => __('Session closed: the result'),
            self::MatchCardReady => __('Your match card is ready'),
            self::AdminCreated => __('New session created'),
            self::AdminFull => __('Session full'),
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
            self::NewPlayer => __(':name has joined your session.', $replace),
            self::JoinConfirmed => __('You\'re registered. Below you\'ll find all the details of the session.'),
            self::SessionFull => __('All spots are taken. Below you\'ll find the full overview.'),
            self::SpotOpened => __(':name has left the session. Share the session again to fill the spot.', $replace),
            self::Reminder => __('Tomorrow you are on the court. A short reminder:'),
            self::OpenSpotsReminder => __('Your session still has :free free places. Share it, or cancel it.', $replace),
            self::Cancelled, self::AdminCancelled => __('The session below has been cancelled by :name.', $replace),
            self::Removed => __(':name has removed you from the session below.', $replace),
            self::JoinRequested => __(':name would like to play in your session. Approve or decline the request.', $replace),
            self::RequestRejected => __('The organiser did not accept your request for the session below.'),
            self::RequestWithdrawn => __(':name has withdrawn the request for your session.', $replace),
            self::ScoreSubmitted => __(':name has submitted a score for your session. Approve or decline the score.', $replace),
            self::ScoreApproved => __('The organiser has approved the score of the session below.'),
            self::ScoreRejected => __('The organiser did not accept the score you submitted.'),
            self::SessionFinished => __('The session is closed. Below you will find the official result.'),
            self::MatchCardReady => __('Thank you for the session at :club. Your match card is ready: share it with your friends or keep it as a memory.', $replace),
            self::AdminCreated => __(':name has created a new session.', $replace),
            self::AdminFull => __('All spots in this session are taken.'),
        };
    }

    /**
     * Short text for the in-app bell. `:result` is the set line ("6-4, 6-3").
     */
    public function inAppMessage(string $name, string $when, int $freePlaces, string $result = '', string $club = ''): string
    {
        $replace = ['name' => $name, 'when' => $when, 'free' => $freePlaces, 'result' => $result, 'club' => $club];

        return match ($this) {
            self::NewPlayer => __(':name is joining your session on :when. Free spots: :free.', $replace),
            self::JoinConfirmed => __('You\'re registered for the session on :when.', $replace),
            self::SessionFull, self::AdminFull => __('The session on :when is full.', $replace),
            self::SpotOpened => __(':name has left the session on :when. Free spots: :free.', $replace),
            self::Reminder => __('Reminder: your session starts on :when.', $replace),
            self::OpenSpotsReminder => __('Your session on :when still has :free free places. Share it, or cancel it.', $replace),
            self::Cancelled, self::AdminCancelled => __('The session on :when has been cancelled by :name.', $replace),
            self::Removed => __(':name has removed you from the session on :when.', $replace),
            self::JoinRequested => __(':name would like to play in your session on :when. Free places: :free.', $replace),
            self::RequestRejected => __('Your request for the session on :when was not accepted.', $replace),
            self::RequestWithdrawn => __(':name has withdrawn the request for your session on :when.', $replace),
            self::ScoreSubmitted => __(':name has submitted a score for the session on :when.', $replace),
            self::ScoreApproved => __('The score of the session on :when has been approved: :result.', $replace),
            self::ScoreRejected => __('Your score for the session on :when was not accepted.', $replace),
            self::SessionFinished => __('The session on :when is closed. Result: :result.', $replace),
            self::MatchCardReady => __('Your match card from :club is ready.', $replace),
            self::AdminCreated => __(':name has created a new session for :when.', $replace),
        };
    }

    public function ctaLabel(): string
    {
        return match ($this) {
            self::SpotOpened => __('Share the session again'),
            self::OpenSpotsReminder => __('Share the session'),
            self::Cancelled, self::Removed, self::RequestRejected => __('View other sessions'),
            self::JoinRequested => __('View request'),
            self::ScoreSubmitted => __('View score'),
            self::ScoreApproved, self::SessionFinished => __('View result'),
            self::MatchCardReady => __('Share your card'),
            default => __('View session'),
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
            self::OpenSpotsReminder => __('Cancel session'),
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
            ? __('Your session is today at :time.', $replace)
            : __('Your session is tomorrow at :time.', $replace);
    }
}
