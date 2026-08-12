import { usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';
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
import { useLocale } from '@/hooks/use-locale';
import { useReveal } from '@/hooks/use-reveal';
import { activePage } from '@/lib/maison-navigation';

type ModalKind = 'newsletter' | 'order' | 'certificate' | 'auth' | null;

type PageProps = {
    flash?: {
        open_auth_modal?: AuthView;
    };
};

function parseAuthQuery(): AuthView | null {
    const value = new URLSearchParams(window.location.search).get('auth');

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

export default function FrontendLayout({ children }: { children: ReactNode }) {
    const main = useRef<HTMLElement>(null);
    const { url, props } = usePage<PageProps>();
    const { locale } = useLocale();
    const [modal, setModal] = useState<ModalKind>(null);
    const [authView, setAuthView] = useState<AuthView>('login');

    useReveal(main);

    const actions = useMemo<ShellActions>(
        () => ({
            openNewsletter: () => setModal('newsletter'),
            openOrder: () => setModal('order'),
            openCertificate: () => setModal('certificate'),
            openAuth: (view: AuthView = 'login') => {
                setAuthView(view);
                setModal('auth');
            },
        }),
        [],
    );

    useEffect(() => {
        const flashView = props.flash?.open_auth_modal;
        const queryView = parseAuthQuery();
        const nextView = flashView ?? queryView;

        if (nextView) {
            setAuthView(nextView);
            setModal('auth');
        }
    }, [props.flash?.open_auth_modal, url]);

    return (
        <ShellActionsProvider value={actions}>
            <PageTransition>
                <div className="min-h-screen bg-cream text-choc">
                    <CinematicLayer />

                    <SiteTopbar onNewsletter={actions.openNewsletter} />
                    <SiteNav />

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

                    {activePage(url, locale) === 'home' && <ImmersiveIntro />}

                    {modal && (
                        <MaisonModals
                            kind={modal}
                            authView={authView}
                            onAuthViewChange={setAuthView}
                            onClose={() => setModal(null)}
                        />
                    )}
                </div>
            </PageTransition>
        </ShellActionsProvider>
    );
}
