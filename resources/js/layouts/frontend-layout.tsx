import { usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { CinematicLayer } from '@/components/maison/cinematic/cinematic-layer';
import { PageTransition } from '@/components/maison/cinematic/page-transition';
import { ImmersiveIntro } from '@/components/maison/intro/immersive-intro';
import { ContactDock } from '@/components/maison/shell/contact-dock';
import { EtchingBand } from '@/components/maison/shell/etching-band';
import { SiteFooter } from '@/components/maison/shell/site-footer';
import { SiteNav } from '@/components/maison/shell/site-nav';
import { SiteTopbar } from '@/components/maison/shell/site-topbar';
import { useLocale } from '@/hooks/use-locale';
import { useReveal } from '@/hooks/use-reveal';
import { activePage } from '@/lib/maison-navigation';

/**
 * The public site shell: the fixed topbar and header, the cinematic overlays,
 * the etching band, the footer and the contact dock.
 *
 * Everything here is rendered exactly once and survives navigation, so the
 * header keeps its state and the scroll reveals rebind per page rather than
 * accumulating observers. Page content is offset by the two header heights
 * through `--topbar-h` and `--nav-h`, which is the only place those numbers
 * appear — the prototype hardcoded the sum as `padding-top: 130px` in one file
 * and `top: 150px` in another, and they had already drifted.
 */
export default function FrontendLayout({ children }: { children: ReactNode }) {
    const main = useRef<HTMLElement>(null);
    const { url } = usePage();
    const { locale } = useLocale();
    const [newsletterOpen, setNewsletterOpen] = useState(false);

    useReveal(main);

    return (
        <PageTransition>
            <div className="min-h-screen bg-cream text-choc">
                <CinematicLayer />

                <SiteTopbar onNewsletter={() => setNewsletterOpen(true)} />
                <SiteNav />

                <main
                    ref={main}
                    id="main"
                    className="pt-[calc(var(--topbar-h)+var(--nav-h))]"
                >
                    {children}
                </main>

                <EtchingBand />
                <SiteFooter onNewsletter={() => setNewsletterOpen(true)} />
                <ContactDock />

                {/*
                 * The arrival sequence belongs to the front door, so it is only
                 * mounted there — and it decides for itself whether this visitor
                 * has already been shown in.
                 */}
                {activePage(url, locale) === 'home' && <ImmersiveIntro />}

                {/*
                 * The newsletter modal itself arrives with the modals step; the
                 * shell already owns the state the topbar and footer toggle.
                 */}
                {newsletterOpen && (
                    <NewsletterPlaceholder
                        onClose={() => setNewsletterOpen(false)}
                    />
                )}
            </div>
        </PageTransition>
    );
}

function NewsletterPlaceholder({ onClose }: { onClose: () => void }) {
    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label="Heritage Letter"
            className="fixed inset-0 z-[9990] flex items-center justify-center bg-choc/80 px-6"
            onClick={onClose}
        >
            <p className="font-sans text-[11px] tracking-[0.3em] text-cream uppercase">
                Heritage Letter
            </p>
        </div>
    );
}
