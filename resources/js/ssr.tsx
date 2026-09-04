import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';
import { I18nProvider } from '@/components/i18n-provider';
import { TooltipProvider } from '@/components/ui/tooltip';
import { createI18nForLocale } from '@/lib/i18n';
import { resolvePageLayout } from '@/lib/inertia-layouts';
import { syncWayfinderLocale } from '@/lib/wayfinder-defaults';
import { isLocale, SOURCE_LOCALE } from '@/types/locale';

const appName = import.meta.env.VITE_APP_NAME || 'Maison Anversa';

createServer((page) => {
    const locale = isLocale(page.props.locale)
        ? page.props.locale
        : SOURCE_LOCALE;

    syncWayfinderLocale(locale);

    // The `@inertiajs/vite` plugin injects a `resolve` option into the
    // `createInertiaApp` call below at build time, so tsc (which never sees
    // that transform) can't match this call against the SSR overload.
    return createI18nForLocale(locale).then((i18n) =>
        createInertiaApp({
            page,
            render: ReactDOMServer.renderToString,
            title: (title: string) => title || appName,
            layout: resolvePageLayout,
            setup: ({ App, props }: { App: any; props: any }) => (
                <I18nProvider i18n={i18n}>
                    <TooltipProvider delayDuration={0}>
                        <App {...props} />
                    </TooltipProvider>
                </I18nProvider>
            ),
        } as any),
    );
});
