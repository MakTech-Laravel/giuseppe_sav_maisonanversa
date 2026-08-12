import '@/lib/wayfinder-defaults';

import { createInertiaApp, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle, Info, XCircle } from 'lucide-react';
import { ErrorBoundary } from 'react-error-boundary';
import { ErrorBoundaryFallback } from '@/components/error-boundary/error-boundary-fallback';
import { pushError } from '@/components/error-boundary/error-store';
import { I18nProvider, readInitialLocale } from '@/components/i18n-provider';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useDevErrorFallback } from '@/hooks/useDevErrorFallback';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import FrontendLayout from '@/layouts/frontend-layout';
import MemberLayout from '@/layouts/member-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { createI18nForLocale } from '@/lib/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/*
 * The dictionary is fetched before the app mounts so the first paint is already
 * in the visitor's language instead of flashing the Dutch source copy.
 */
const i18n = await createI18nForLocale(readInitialLocale());

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('maison/'):
                return FrontendLayout;
            case name.startsWith('member/'):
                return MemberLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                <ErrorBoundary
                    FallbackComponent={ErrorBoundaryFallback}
                    onReset={() => router.reload()}
                    onError={(error, info) => {
                        // Push into persistent history (sessionStorage)
                        pushError(error, info.componentStack ?? null);

                        if (useDevErrorFallback()) {
                            console.error(error, info.componentStack);
                        }
                    }}
                >
                    <I18nProvider i18n={i18n}>{app}</I18nProvider>
                </ErrorBoundary>
                <Toaster
                    position="bottom-right"
                    richColors
                    closeButton
                    expand={true}
                    duration={3000}
                    icons={{
                        success: <CheckCircle className="h-4 w-4" />,
                        error: <XCircle className="h-4 w-4" />,
                        warning: <AlertTriangle className="h-4 w-4" />,
                        info: <Info className="h-4 w-4" />,
                    }}
                />
            </TooltipProvider>
        );
    },
    progress: {
        color: 'var(--primary)',
    },
});
