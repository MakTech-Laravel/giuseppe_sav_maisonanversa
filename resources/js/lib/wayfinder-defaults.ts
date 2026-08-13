import { getInitialPageFromDOM } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { isLocale, SOURCE_LOCALE } from '@/types/locale';
import type { Locale } from '@/types/locale';
import { setUrlDefaults } from '@/wayfinder';

let activeLocale: Locale = SOURCE_LOCALE;

function resolveLocale(candidate: unknown): Locale {
    return isLocale(candidate) ? candidate : SOURCE_LOCALE;
}

/**
 * Return an explicit locale for generated routes. This avoids relying on
 * module-scoped URL defaults when Vite hot-reloads Wayfinder modules.
 */
export function wayfinderLocale(): Locale {
    if (typeof window !== 'undefined') {
        const segment = window.location.pathname.split('/').filter(Boolean)[0];

        return resolveLocale(segment);
    }

    return activeLocale;
}

/**
 * Wayfinder route helpers need `{locale}` after authenticated routes moved under
 * the locale prefix. Keep defaults in sync with the shared Inertia locale prop
 * so callers can omit it (or rely on URL::defaults from the server).
 */
export function syncWayfinderLocale(locale: unknown): void {
    activeLocale = resolveLocale(locale);
    setUrlDefaults({ locale: activeLocale });
}

function readBootLocale(): Locale {
    try {
        const page = getInitialPageFromDOM<{ props?: { locale?: unknown } }>(
            'app',
        );

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
