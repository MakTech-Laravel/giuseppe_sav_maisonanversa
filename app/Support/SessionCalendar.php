<?php

namespace App\Support;

use App\Models\CommunitySession;
use Carbon\CarbonInterface;

/**
 * A single-event iCalendar file for a session, so players can add it to
 * their calendar straight from the mail. The UID is stable per session, so
 * importing E2 and later E3 updates one entry instead of creating two.
 */
class SessionCalendar
{
    public static function make(CommunitySession $session, string $url): string
    {
        $session->loadMissing('club');

        $club = $session->club;
        $summary = trim('Maison Anversa · '.__($session->sport->label()).($club ? ' · '.$club->name : ''));
        $location = $club ? implode(', ', array_filter([$club->name, $club->addressLine()])) : '';
        $endsAt = $session->ends_at ?? $session->starts_at->copy()->addMinutes($session->duration_minutes ?? 90);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Maison Anversa//Sessions//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:session-'.$session->id.'@maisonanversa',
            'DTSTAMP:'.self::utc(now()),
            'DTSTART:'.self::utc($session->starts_at),
            'DTEND:'.self::utc($endsAt),
            'SUMMARY:'.self::escape($summary),
            'LOCATION:'.self::escape($location),
            'URL:'.$url,
            'DESCRIPTION:'.self::escape($url),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    private static function utc(CarbonInterface $moment): string
    {
        return $moment->copy()->utc()->format('Ymd\THis\Z');
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $value);
    }

    /**
     * RFC 5545 limits lines to 75 octets; continuation lines start with a space.
     */
    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $chunks = [];

        while (strlen($line) > 75) {
            $cut = 75;

            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }

            $chunks[] = substr($line, 0, $cut);
            $line = ' '.substr($line, $cut);
        }

        $chunks[] = $line;

        return implode("\r\n", $chunks);
    }
}
