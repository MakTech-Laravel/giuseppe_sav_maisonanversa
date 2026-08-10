import { getInitialPageFromDOM } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import type { i18n as I18n } from 'i18next';
import { useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import { I18nextProvider } from 'react-i18next';
import { switchInstanceLocale } from '@/lib/i18n';
import { isLocale, SOURCE_LOCALE } from '@/types/locale';
import type { Locale } from '@/types/locale';

/**
 * The locale of the first page render, taken from the server-shared prop that
 * Inertia embeds in the root element. Read outside React because the instance
 * has to exist before the tree mounts, which avoids a flash of Dutch copy.
 */
export function readInitialLocale(): Locale {
    const page = getInitialPageFromDOM<{ props?: { locale?: unknown } }>('app');
    const locale = page?.props?.locale;

    return isLocale(locale) ? locale : SOURCE_LOCALE;
}

/**
 * Keeps the i18next instance in step with the locale the server reports.
 *
 * The provider sits outside Inertia's page context, so the locale arrives via
 * navigation events rather than `usePage()`. Both read the same shared prop.
 */
export function I18nProvider({
    i18n,
    children,
}: {
    i18n: I18n;
    children: ReactNode;
}) {
    const [locale, setLocale] = useState<Locale>(readInitialLocale);

    useEffect(
        () =>
            router.on('navigate', (event) => {
                const next = event.detail.page.props.locale;

                if (isLocale(next)) {
                    setLocale(next);
                }
            }),
        [],
    );

    useEffect(() => {
        document.documentElement.lang = locale;

        if (locale !== i18n.language) {
            void switchInstanceLocale(i18n, locale);
        }
    }, [i18n, locale]);

    return <I18nextProvider i18n={i18n}>{children}</I18nextProvider>;
}
