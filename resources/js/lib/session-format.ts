import type { Locale } from '@/types/locale';

const INTL_LOCALES: Record<string, string> = {
    nl: 'nl-BE',
    en: 'en-GB',
    fr: 'fr-BE',
};

/** Antwerp / Belgium — all Maison session times are this wall clock. */
export const SESSION_TIME_ZONE = 'Europe/Brussels';

function intlLocale(locale: Locale | string): string {
    return INTL_LOCALES[locale] ?? 'nl-BE';
}

function sessionDateOptions(
    options: Intl.DateTimeFormatOptions,
): Intl.DateTimeFormatOptions {
    return { ...options, timeZone: SESSION_TIME_ZONE };
}

/** "Za 24 mei" — the short weekday + day + month the session cards use. */
export function formatSessionDate(
    iso: string,
    locale: Locale | string,
): string {
    return new Date(iso).toLocaleDateString(
        intlLocale(locale),
        sessionDateOptions({
            weekday: 'short',
            day: 'numeric',
            month: 'short',
        }),
    );
}

/** Split parts for the homepage session date block (e.g. Di / 6 / Okt). */
export function formatSessionDateParts(
    iso: string,
    locale: Locale | string,
): { weekday: string; day: string; month: string } {
    const date = new Date(iso);
    const resolved = intlLocale(locale);

    return {
        weekday: date.toLocaleDateString(
            resolved,
            sessionDateOptions({ weekday: 'short' }),
        ),
        day: date.toLocaleDateString(
            resolved,
            sessionDateOptions({ day: 'numeric' }),
        ),
        month: date.toLocaleDateString(
            resolved,
            sessionDateOptions({ month: 'short' }),
        ),
    };
}

/** "24 mei 2025" for the fuller detail header. Dutch months stay lowercase. */
export function formatSessionDateLong(
    iso: string,
    locale: Locale | string,
): string {
    const formatted = new Date(iso).toLocaleDateString(
        intlLocale(locale),
        sessionDateOptions({
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }),
    );

    if (locale === 'nl' || locale.startsWith('nl')) {
        return formatted.replace(
            /(\d+)\s+(\p{L}+)/u,
            (_, day: string, month: string) =>
                `${day} ${month.toLocaleLowerCase('nl-BE')}`,
        );
    }

    return formatted;
}

/** "10:30" in 24-hour form, which is what the mockup shows. */
export function formatSessionTime(
    iso: string,
    locale: Locale | string,
): string {
    return new Date(iso).toLocaleTimeString(
        intlLocale(locale),
        sessionDateOptions({
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }),
    );
}

/** "24 mei 2025, 10:30" for admin tables and dashboards. */
export function formatSessionDateTime(
    iso: string,
    locale: Locale | string,
): string {
    return `${formatSessionDateLong(iso, locale)}, ${formatSessionTime(iso, locale)}`;
}

export function formatDuration(minutes: number): string {
    return `${minutes} min`;
}

/** The value an `<input type="datetime-local">` pair needs, in local time. */
export function toDateInputValue(date: Date): string {
    const offset = date.getTimezoneOffset() * 60_000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 10);
}

/** Parse a `YYYY-MM-DD` value as a local calendar date. */
export function parseDateInputValue(value: string): Date | undefined {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);

    if (!match) {
        return undefined;
    }

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    const parsed = new Date(year, month - 1, day);

    return Number.isNaN(parsed.getTime()) ? undefined : parsed;
}

export function combineDateAndTime(date: string, time: string): string {
    return `${date}T${time}`;
}
