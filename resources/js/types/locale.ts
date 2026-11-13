/**
 * The locales the public site is published in, in language-switcher order.
 * Mirrors `config/maison.php`.
 */
export const LOCALES = ['nl', 'en', 'fr'] as const;

export type Locale = (typeof LOCALES)[number];

/**
 * English is the source language: every translation key is the English copy, so
 * it has no dictionary of its own. NL/FR live in lang/nl.json and lang/fr.json.
 */
export const SOURCE_LOCALE = 'en' satisfies Locale;

export function isLocale(value: unknown): value is Locale {
    return typeof value === 'string' && LOCALES.includes(value as Locale);
}
