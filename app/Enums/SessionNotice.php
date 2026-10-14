<?php

namespace App\Enums;

/**
 * The session notifications from the client brief (E1-E6), plus the removed
 * player's notice and the three admin events. Every string is Dutch source
 * copy and is resolved in whatever locale is active when it is called, so
 * callers switch to the recipient's locale first.
 */
enum SessionNotice: string
{
    /** E1: a member joins, to the organiser. */
    case NewPlayer = 'new_player';

    /** E2: a member joins, to that member. */
    case JoinConfirmed = 'join_confirmed';

    /** E3: the session became full, to every player including the organiser. */
    case SessionFull = 'session_full';

    /** E4: a player left, to the organiser (and the others if it was full). */
    case SpotOpened = 'spot_opened';

    /** E5: 24 hours before the start, to every player. */
    case Reminder = 'reminder';

    /** E6: the session was cancelled, to every player. */
    case Cancelled = 'cancelled';

    case Removed = 'removed';

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
            self::Reminder => __('Morgen op het terrein'),
            self::Cancelled, self::AdminCancelled => __('Sessie geannuleerd'),
            self::Removed => __('U bent uit een sessie verwijderd'),
            self::AdminCreated => __('Nieuwe sessie aangemaakt'),
            self::AdminFull => __('Sessie volzet'),
        };
    }

    /**
     * Opening line of the mail. `:name` is the member the notice is about.
     */
    public function intro(string $name): string
    {
        $replace = ['name' => $name];

        return match ($this) {
            self::NewPlayer => __(':name heeft zich aangemeld voor uw sessie.', $replace),
            self::JoinConfirmed => __('U bent ingeschreven. Hieronder vindt u alle details van de sessie.'),
            self::SessionFull => __('Alle plaatsen zijn ingenomen. Hieronder vindt u het volledige overzicht.'),
            self::SpotOpened => __(':name heeft de sessie verlaten. Deel de sessie opnieuw om de plaats in te vullen.', $replace),
            self::Reminder => __('Morgen staat u op het terrein. Een korte herinnering:'),
            self::Cancelled, self::AdminCancelled => __('De onderstaande sessie is geannuleerd door :name.', $replace),
            self::Removed => __(':name heeft u verwijderd uit de onderstaande sessie.', $replace),
            self::AdminCreated => __(':name heeft een nieuwe sessie aangemaakt.', $replace),
            self::AdminFull => __('Alle plaatsen van deze sessie zijn ingenomen.'),
        };
    }

    /**
     * Short text for the in-app bell.
     */
    public function inAppMessage(string $name, string $when, int $freePlaces): string
    {
        $replace = ['name' => $name, 'when' => $when, 'free' => $freePlaces];

        return match ($this) {
            self::NewPlayer => __(':name speelt mee in uw sessie van :when. Vrije plaatsen: :free.', $replace),
            self::JoinConfirmed => __('U bent ingeschreven voor de sessie van :when.', $replace),
            self::SessionFull, self::AdminFull => __('De sessie van :when is volzet.', $replace),
            self::SpotOpened => __(':name heeft de sessie van :when verlaten. Vrije plaatsen: :free.', $replace),
            self::Reminder => __('Herinnering: uw sessie begint op :when.', $replace),
            self::Cancelled, self::AdminCancelled => __('De sessie van :when is geannuleerd door :name.', $replace),
            self::Removed => __(':name heeft u verwijderd uit de sessie van :when.', $replace),
            self::AdminCreated => __(':name heeft een nieuwe sessie aangemaakt voor :when.', $replace),
        };
    }

    public function ctaLabel(): string
    {
        return match ($this) {
            self::SpotOpened => __('Deel de sessie opnieuw'),
            self::Cancelled, self::Removed => __('Bekijk andere sessies'),
            default => __('Bekijk sessie'),
        };
    }

    /**
     * Which detail rows the mail shows, in order.
     *
     * @return list<'club'|'address'|'route'|'date'|'time'|'level'|'free'|'players'|'message'>
     */
    public function details(): array
    {
        return match ($this) {
            self::NewPlayer => ['club', 'date', 'time', 'level', 'free'],
            self::JoinConfirmed => ['club', 'address', 'date', 'time', 'level', 'players'],
            self::SessionFull, self::AdminFull => ['club', 'address', 'route', 'date', 'time', 'level', 'players'],
            self::SpotOpened => ['club', 'date', 'time', 'free'],
            self::Reminder => ['club', 'time', 'address', 'route'],
            self::Cancelled, self::AdminCancelled => ['club', 'date', 'time', 'level', 'message'],
            self::Removed => ['club', 'date', 'time'],
            self::AdminCreated => ['club', 'date', 'time', 'level', 'free'],
        };
    }

    public function attachesCalendar(): bool
    {
        return $this === self::JoinConfirmed || $this === self::SessionFull;
    }
}
