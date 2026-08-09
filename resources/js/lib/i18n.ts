import { createInstance } from 'i18next';
import type { i18n as I18n } from 'i18next';
import { initReactI18next } from 'react-i18next';
import { LOCALES, SOURCE_LOCALE } from '@/types/locale';
import type { Locale } from '@/types/locale';

/**
 * Translation keys are the Dutch source copy itself, matching Laravel's JSON
 * translation convention in `lang/*.json`.
 */
export type Dictionary = Record<string, string>;

const NAMESPACE = 'translation';

/**
 * Dutch needs no dictionary: a lookup miss returns the key, which is already
 * the Dutch copy. The other locales are code-split so a visitor only downloads
 * the one they are reading.
 */
const loaders: Record<
    Exclude<Locale, typeof SOURCE_LOCALE>,
    () => Promise<{ default: Dictionary }>
> = {
    en: () => import('@lang/en.json'),
    fr: () => import('@lang/fr.json'),
};

const cache = new Map<Locale, Dictionary>([[SOURCE_LOCALE, {}]]);

export async function loadDictionary(locale: Locale): Promise<Dictionary> {
    const cached = cache.get(locale);

    if (cached) {
        return cached;
    }

    const { default: dictionary } =
        await loaders[locale as Exclude<Locale, typeof SOURCE_LOCALE>]();

    cache.set(locale, dictionary);

    return dictionary;
}

/**
 * Build an isolated i18next instance rather than mutating the package-level
 * singleton, so a server-rendered request can never leak its locale into
 * another one.
 */
export function createI18n(locale: Locale, dictionary: Dictionary): I18n {
    const instance = createInstance();

    instance.use(initReactI18next).init({
        lng: locale,
        supportedLngs: [...LOCALES],
        ns: [NAMESPACE],
        defaultNS: NAMESPACE,
        resources: { [locale]: { [NAMESPACE]: dictionary } },

        /*
         * Keys are whole Dutch sentences, so the defaults would misread them:
         * `.` would be treated as a path into a nested object and `:` as a
         * namespace prefix.
         */
        keySeparator: false,
        nsSeparator: false,

        // A miss must return the key, because the key is the Dutch source copy.
        fallbackLng: false,
        returnEmptyString: false,

        // React escapes on render; escaping here would double-encode.
        interpolation: { escapeValue: false },

        saveMissing: import.meta.env.DEV,
        missingKeyHandler: (lngs, _ns, key) => {
            // Dutch has no dictionary by design: every key misses, correctly.
            const missing = lngs.filter((lng) => lng !== SOURCE_LOCALE);

            if (missing.length > 0) {
                console.warn(
                    `[i18n] missing ${missing.join(', ')} translation: ${key}`,
                );
            }
        },

        react: {
            // Lets <Trans> keep the inline emphasis the copy is written with.
            transKeepBasicHtmlNodesFor: ['br', 'strong', 'em', 'i'],
        },
    });

    return instance;
}

/**
 * Resolve the dictionary for a locale and return a ready-to-use instance.
 */
export async function createI18nForLocale(locale: Locale): Promise<I18n> {
    return createI18n(locale, await loadDictionary(locale));
}

/**
 * Teach an existing instance a locale it has not seen yet, then switch to it.
 */
export async function switchInstanceLocale(
    instance: I18n,
    locale: Locale,
): Promise<void> {
    if (!instance.hasResourceBundle(locale, NAMESPACE)) {
        instance.addResourceBundle(
            locale,
            NAMESPACE,
            await loadDictionary(locale),
        );
    }

    await instance.changeLanguage(locale);
}
