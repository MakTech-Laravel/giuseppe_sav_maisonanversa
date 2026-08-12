import { getInitialPageFromDOM } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { isLocale, SOURCE_LOCALE } from '@/types/locale';
import type { Locale } from '@/types/locale';
import { setUrlDefaults } from '@/wayfinder';

function resolveLocale(candidate: unknown): Locale {
    return isLocale(candidate) ? candidate : SOURCE_LOCALE;
}

/**
 * Wayfinder route helpers need `{locale}` after authenticated routes moved under
 * the locale prefix. Keep defaults in sync with the shared Inertia locale prop
 * so callers can omit it (or rely on URL::defaults from the server).
 */
export function syncWayfinderLocale(locale: unknown): void {
    setUrlDefaults({ locale: resolveLocale(locale) });
}

function readBootLocale(): Locale {
    try {
        const page = getInitialPageFromDOM<{ props?: { locale?: unknown } }>('app');

        return resolveLocale(page?.props?.locale);
    } catch {
        return SOURCE_LOCALE;
    }
}

syncWayfinderLocale(readBootLocale());

if (typeof window !== 'undefined') {
    router.on('navigate', (event) => {
        syncWayfinderLocale(event.detail.page.props.locale);
    });
}
