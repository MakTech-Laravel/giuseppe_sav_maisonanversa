import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { loadDictionary } from '@/lib/i18n';
import { isLocale, LOCALES, SOURCE_LOCALE } from '@/types/locale';
import type { Locale } from '@/types/locale';

/**
 * Swap the locale segment of a site URL, keeping the rest of the path, the
 * query string and the hash intact. Every public route is locale-prefixed, so
 * the first segment is always the locale.
 */
export function localizePath(path: string, locale: Locale): string {
    const [pathname, ...rest] = path.split(/(?=[?#])/);
    const segments = pathname.split('/').filter((segment) => segment !== '');

    if (segments.length > 0 && isLocale(segments[0])) {
        segments[0] = locale;
    } else {
        segments.unshift(locale);
    }

    return `/${segments.join('/')}${rest.join('')}`;
}

export function useLocale() {
    const { locale, availableLocales } = usePage().props;
    const { t } = useTranslation();

    const current = isLocale(locale) ? locale : SOURCE_LOCALE;
    const locales = availableLocales ?? [...LOCALES];

    /**
     * Fetch the dictionary before navigating so the new page paints in its own
     * language rather than briefly falling back to the Dutch source copy.
     */
    async function switchLocale(next: Locale): Promise<void> {
        if (next === current) {
            return;
        }

        await loadDictionary(next);

        const { pathname, search, hash } = window.location;

        router.visit(localizePath(`${pathname}${search}${hash}`, next), {
            preserveScroll: true,
        });
    }

    return { locale: current, availableLocales: locales, switchLocale, t };
}
