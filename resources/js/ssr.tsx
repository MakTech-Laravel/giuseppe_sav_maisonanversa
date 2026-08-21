import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { I18nProvider } from '@/components/i18n-provider';
import { createI18nForLocale } from '@/lib/i18n';
import { resolvePageLayout } from '@/lib/inertia-layouts';
import { syncWayfinderLocale } from '@/lib/wayfinder-defaults';
import { isLocale, SOURCE_LOCALE } from '@/types/locale';
import ReactDOMServer from 'react-dom/server';

const appName = import.meta.env.VITE_APP_NAME || 'Maison Anversa';

createServer((page) => {
    const locale = isLocale(page.props.locale) ? page.props.locale : SOURCE_LOCALE;

    syncWayfinderLocale(locale);

    return createI18nForLocale(locale).then((i18n) =>
        createInertiaApp({
            page,
            render: ReactDOMServer.renderToString,
            title: (title) => title || appName,
            layout: resolvePageLayout,
            setup: ({ App, props }) => (
                <I18nProvider i18n={i18n}>
                    <App {...props} />
                </I18nProvider>
            ),
        }),
    );
});
