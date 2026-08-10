/**
 * The locales the public site is published in, in language-switcher order.
 * Mirrors `config/maison.php`.
 */
export const LOCALES = ['nl', 'en', 'fr'] as const;

export type Locale = (typeof LOCALES)[number];

/**
 * Dutch is the source language: every translation key is the Dutch copy, so it
 * has no dictionary of its own.
 */
export const SOURCE_LOCALE = 'nl' satisfies Locale;

export function isLocale(value: unknown): value is Locale {
    return typeof value === 'string' && LOCALES.includes(value as Locale);
}
