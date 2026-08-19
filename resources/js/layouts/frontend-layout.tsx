import { usePage } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { CinematicLayer } from '@/components/maison/cinematic/cinematic-layer';
import { PageTransition } from '@/components/maison/cinematic/page-transition';
import { ImmersiveIntro } from '@/components/maison/intro/immersive-intro';
import type { AuthView } from '@/components/maison/modals/auth-modal';
import { MaisonModals } from '@/components/maison/modals/maison-modals';
import { ContactDock } from '@/components/maison/shell/contact-dock';
import { EtchingBand } from '@/components/maison/shell/etching-band';
import { ShellActionsProvider } from '@/components/maison/shell/shell-actions';
import type { ShellActions } from '@/components/maison/shell/shell-actions';
import { SiteFooter } from '@/components/maison/shell/site-footer';
import { SiteNav } from '@/components/maison/shell/site-nav';
import { SiteTopbar } from '@/components/maison/shell/site-topbar';
import { CookieConsentBanner } from '@/components/maison/cookie-consent-banner';
import { useLocale } from '@/hooks/use-locale';
import { useReveal } from '@/hooks/use-reveal';
import { activePage } from '@/lib/maison-navigation';

type ModalKind = 'newsletter' | 'order' | 'certificate' | 'auth' | null;

type PageProps = {
    flash?: {
        open_auth_modal?: AuthView;
    };
};

function parseAuthFromUrl(pageUrl: string): AuthView | null {
    const queryIndex = pageUrl.indexOf('?');

    if (queryIndex === -1) {
        return null;
    }

    const value = new URLSearchParams(pageUrl.slice(queryIndex)).get('auth');

    if (
        value === 'login' ||
        value === 'register' ||
        value === 'forgot' ||
        value === 'two-factor'
    ) {
        return value;
    }

    return null;
}

function authPromptKey(
    flashView: AuthView | undefined,
    pageUrl: string,
): string {
    return `${flashView ?? ''}|${pageUrl}`;
}

export default function FrontendLayout({ children }: { children: ReactNode }) {
    const main = useRef<HTMLElement>(null);
    const { url, props } = usePage<PageProps>();
    const { locale } = useLocale();
    const { t } = useTranslation();
    const [userModal, setUserModal] = useState<ModalKind>(null);
    const [userAuthView, setUserAuthView] = useState<AuthView>('login');
    const [dismissedAuthPromptKey, setDismissedAuthPromptKey] = useState<
        string | null
    >(null);

    const promptedAuthView =
        props.flash?.open_auth_modal ?? parseAuthFromUrl(url);
    const currentAuthPromptKey = authPromptKey(
        props.flash?.open_auth_modal,
        url,
    );
    const autoOpenAuth =
        promptedAuthView !== null &&
        dismissedAuthPromptKey !== currentAuthPromptKey;
    const modal = userModal ?? (autoOpenAuth ? 'auth' : null);
    const authView =
        userModal === 'auth'
            ? userAuthView
            : (promptedAuthView ?? 'login');

    useReveal(main);

    const actions = useMemo<ShellActions>(
        () => ({
            openNewsletter: () => setUserModal('newsletter'),
            openOrder: () => setUserModal('order'),
            openCertificate: () => setUserModal('certificate'),
            openAuth: (view: AuthView = 'login') => {
                setUserAuthView(view);
                setUserModal('auth');
            },
        }),
        [],
    );

    function closeModal(): void {
        if (autoOpenAuth && userModal === null) {
            setDismissedAuthPromptKey(currentAuthPromptKey);
        }

        setUserModal(null);
    }

    return (
        <ShellActionsProvider value={actions}>
            <PageTransition>
                <div className="min-h-screen bg-cream text-choc">
                    <a
                        href="#main"
                        className="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-[300] focus:bg-cream focus:px-4 focus:py-2 focus:font-sans focus:text-sm focus:text-choc"
                    >
                        {t('Ga naar de inhoud')}
                    </a>
                    <CinematicLayer />

                    <header>
                        <SiteTopbar onNewsletter={actions.openNewsletter} />
                        <SiteNav />
                    </header>

                    <main
                        ref={main}
                        id="main"
                        className="pt-[calc(var(--topbar-h)+var(--nav-h))]"
                    >
                        {children}
                    </main>

                    <SiteFooter onNewsletter={actions.openNewsletter} />
                    <EtchingBand />
                    <ContactDock />
                    <CookieConsentBanner />

                    {activePage(url, locale) === 'home' && <ImmersiveIntro />}

                    {modal && (
                        <MaisonModals
                            kind={modal}
                            authView={authView}
                            onAuthViewChange={setUserAuthView}
                            onClose={closeModal}
                        />
                    )}
                </div>
            </PageTransition>
        </ShellActionsProvider>
    );
}
