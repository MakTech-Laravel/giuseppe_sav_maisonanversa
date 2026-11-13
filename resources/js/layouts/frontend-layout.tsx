import { usePage } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { CinematicLayer } from '@/components/maison/cinematic/cinematic-layer';
import { PageTransition } from '@/components/maison/cinematic/page-transition';
import { CookieConsentBanner } from '@/components/maison/cookie-consent-banner';
import type { AuthView } from '@/components/maison/modals/auth-modal';
import { MaisonModals } from '@/components/maison/modals/maison-modals';
import { Opening } from '@/components/maison/opening/opening';
import { ContactDock } from '@/components/maison/shell/contact-dock';
import { EtchingBand } from '@/components/maison/shell/etching-band';
import { ShellActionsProvider } from '@/components/maison/shell/shell-actions';
import type {
    OrderProductContext,
    PreselectedEdition,
    ShellActions,
} from '@/components/maison/shell/shell-actions';
import { SiteFooter } from '@/components/maison/shell/site-footer';
import { SiteNav } from '@/components/maison/shell/site-nav';
import { SiteTopbar } from '@/components/maison/shell/site-topbar';
import { RealtimeListeners } from '@/components/realtime/realtime-listeners';
import { useLocale } from '@/hooks/use-locale';
import { useReveal } from '@/hooks/use-reveal';
import { activePage } from '@/lib/maison-navigation';
import {
    clearPendingPurchase,
    storePendingPurchase,
    takePendingPurchase,
} from '@/lib/pending-purchase';
import type { PendingPurchase } from '@/lib/pending-purchase';

type ModalKind =
    | 'newsletter'
    | 'order'
    | 'edition-picker'
    | 'certificate'
    | 'auth'
    | null;

type PageProps = {
    auth?: {
        user: Record<string, unknown> | null;
    };
    flash?: {
        open_auth_modal?: AuthView;
        status?: string | null;
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

function clampAuthView(
    view: AuthView | null | undefined,
    registrationEnabled: boolean,
): AuthView | null {
    if (view == null) {
        return null;
    }

    if (view === 'register' && !registrationEnabled) {
        return 'login';
    }

    return view;
}

function authPromptKey(
    flashView: AuthView | undefined,
    pageUrl: string,
): string {
    return `${flashView ?? ''}|${pageUrl}`;
}

export default function FrontendLayout({ children }: { children: ReactNode }) {
    const main = useRef<HTMLElement>(null);
    const { url, props } = usePage<
        PageProps & { checkout: OrderProductContext }
    >();
    const { locale } = useLocale();
    const { t } = useTranslation();
    const [userModal, setUserModal] = useState<ModalKind>(null);
    const [orderProduct, setOrderProduct] = useState<
        OrderProductContext | undefined
    >(undefined);
    const [preselectedEdition, setPreselectedEdition] = useState<
        PreselectedEdition | undefined
    >(undefined);
    const [userAuthView, setUserAuthView] = useState<AuthView>('login');
    const [dismissedAuthPromptKey, setDismissedAuthPromptKey] = useState<
        string | null
    >(null);

    const registrationEnabled = props.registration?.enabled !== false;
    const promptedAuthView = clampAuthView(
        props.flash?.open_auth_modal ?? parseAuthFromUrl(url),
        registrationEnabled,
    );
    const currentAuthPromptKey = authPromptKey(
        props.flash?.open_auth_modal,
        url,
    );
    const isAuthenticated = props.auth?.user != null;
    const autoOpenAuth =
        !isAuthenticated &&
        promptedAuthView !== null &&
        dismissedAuthPromptKey !== currentAuthPromptKey;
    const modal = userModal ?? (autoOpenAuth ? 'auth' : null);
    // Prefer flash-driven auth view (e.g. post password-reset) over stale local state.
    const authView = promptedAuthView ?? userAuthView;

    useEffect(() => {
        const flashView = clampAuthView(
            props.flash?.open_auth_modal,
            registrationEnabled,
        );

        if (!flashView || isAuthenticated) {
            return;
        }

        setUserAuthView(flashView);
        setUserModal('auth');
        setDismissedAuthPromptKey(null);
    }, [
        props.flash?.open_auth_modal,
        currentAuthPromptKey,
        isAuthenticated,
        registrationEnabled,
    ]);

    /*
     * Login keeps FrontendLayout mounted when the intended URL is a public
     * Maison page. Clear the stale auth modal so AuthMenu can show the
     * profile initial beside the menu toggle instead of staying behind Log in.
     */
    useEffect(() => {
        if (!isAuthenticated || userModal !== 'auth') {
            return;
        }

        setUserModal(null);
        setDismissedAuthPromptKey(currentAuthPromptKey);
    }, [isAuthenticated, userModal, currentAuthPromptKey]);

    useReveal(main);

    /**
     * `resume` is true when the guest is in the middle of a reservation: the
     * server then remembers the home page too, so login brings them back to it.
     */
    const rememberAuthReturnUrl = useCallback((resume = false): void => {
        const returnUrl = `${window.location.pathname}${window.location.search}`;
        const token = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='))
            ?.split('=')
            .slice(1)
            .join('=');

        if (!token) {
            return;
        }

        void fetch('/auth/intended', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(token),
            },
            body: JSON.stringify(
                resume ? { url: returnUrl, resume: true } : { url: returnUrl },
            ),
        });
    }, []);

    const purchasesPaused = props.checkout?.enabled === false;

    /*
     * A guest who starts a reservation is asked to log in first. What they were
     * doing is kept (the product and the number they chose) and the page they
     * are on is remembered, home included; once they are signed in, the effect
     * below carries on with the same number.
     */
    const requireAuthForCheckout = useCallback(
        (pending?: PendingPurchase): boolean => {
            if (purchasesPaused) {
                return false;
            }

            if (props.auth?.user) {
                return true;
            }

            if (pending) {
                storePendingPurchase(pending);
            }

            rememberAuthReturnUrl(pending !== undefined);
            setUserAuthView('login');
            setUserModal('auth');

            return false;
        },
        [props.auth?.user, purchasesPaused, rememberAuthReturnUrl],
    );

    const actions = useMemo<ShellActions>(
        () => ({
            openNewsletter: () => setUserModal('newsletter'),
            openOrder: (product?: OrderProductContext) => {
                if (!requireAuthForCheckout({ kind: 'order', product })) {
                    return;
                }

                setOrderProduct(product);
                setUserModal('order');
            },
            openPurchase: (
                product?: OrderProductContext,
                edition?: PreselectedEdition,
            ) => {
                if (
                    !requireAuthForCheckout({
                        kind: 'purchase',
                        product,
                        edition,
                    })
                ) {
                    return;
                }

                setPreselectedEdition(edition);

                const context = product ?? props.checkout;
                const isLimited =
                    (context?.productType ?? 'limited_edition') !== 'simple';

                setOrderProduct(context);

                if (isLimited && !context?.editionPieceId) {
                    setUserModal('edition-picker');

                    return;
                }

                setUserModal('order');
            },
            openCertificate: () => setUserModal('certificate'),
            openAuth: (view: AuthView = 'login') => {
                rememberAuthReturnUrl();
                setUserAuthView(
                    clampAuthView(view, registrationEnabled) ?? 'login',
                );
                setUserModal('auth');
            },
        }),
        [
            props.checkout,
            rememberAuthReturnUrl,
            registrationEnabled,
            requireAuthForCheckout,
        ],
    );

    /*
     * Signed in after starting a reservation (login, registration or two-factor):
     * carry on with the same product and number, through the same steps as a
     * signed-in visitor. Taken once, so it never runs twice.
     */
    useEffect(() => {
        if (!isAuthenticated) {
            return;
        }

        const pending = takePendingPurchase();

        if (pending === null) {
            return;
        }

        if (pending.kind === 'order') {
            actions.openOrder(pending.product);

            return;
        }

        actions.openPurchase(pending.product, pending.edition);
    }, [isAuthenticated, actions]);

    function closeModal(): void {
        if (autoOpenAuth || modal === 'auth') {
            setDismissedAuthPromptKey(currentAuthPromptKey);
        }

        // Closing the login window cancels the reservation that was waiting on it.
        clearPendingPurchase();

        setUserModal(null);
        setOrderProduct(undefined);
        setPreselectedEdition(undefined);
    }

    function onEditionSelected(product: OrderProductContext): void {
        setPreselectedEdition(undefined);
        setOrderProduct(product);
        setUserModal('order');
    }

    return (
        <ShellActionsProvider value={actions}>
            <PageTransition>
                <div className="min-h-screen bg-cream font-sans font-light text-choc lining-nums">
                    <a
                        href="#main"
                        className="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-[300] focus:bg-cream focus:px-4 focus:py-2 focus:font-sans focus:text-sm focus:text-choc"
                    >
                        {t('Skip to content')}
                    </a>
                    <CinematicLayer />
                    {isAuthenticated && <RealtimeListeners />}

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

                    {activePage(url, locale) === 'home' && <Opening />}

                    {modal && (
                        <MaisonModals
                            kind={modal}
                            authView={authView}
                            orderProduct={orderProduct}
                            preselectedEdition={preselectedEdition}
                            onAuthViewChange={setUserAuthView}
                            onClose={closeModal}
                            onEditionSelected={onEditionSelected}
                        />
                    )}
                </div>
            </PageTransition>
        </ShellActionsProvider>
    );
}
