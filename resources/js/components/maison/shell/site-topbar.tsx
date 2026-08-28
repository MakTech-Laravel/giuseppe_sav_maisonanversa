import { usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import type { SiteShared } from '@/components/maison/contact/contact-data';
import { AuthMenu } from '@/components/maison/shell/auth-menu';

/**
 * The 40px band above the header. Its left and right groups drop away below
 * 768px, leaving only the edition line centred.
 *
 * Heritage Letter (newsletter signup) stays visible for guests and members;
 * AuthMenu handles Log in vs the signed-in profile control.
 */
export function SiteTopbar({ onNewsletter }: { onNewsletter: () => void }) {
    const { t } = useTranslation();
    const announcement = usePage<{ site: SiteShared }>().props.site
        ?.announcementText;

    return (
        <div className="fixed inset-x-0 top-0 z-[200] flex h-[var(--topbar-h)] items-center justify-between gap-3 border-b border-gold/20 bg-choc px-4 ma-lg:px-12">
            <span className="hidden shrink-0 font-sans text-[10px] font-light tracking-[0.22em] text-cream uppercase ma-lg:inline">
                Maison Anversa
            </span>

            <span className="min-w-0 truncate text-center font-serif text-[10px] font-medium tracking-[0.12em] text-gold uppercase ma-lg:text-[13px] ma-lg:tracking-[0.2em]">
                {announcement || t('Eerste Editie — Beperkt tot 100 Stuks')}
            </span>

            <div className="hidden items-center gap-6 ma-lg:flex">
                <button
                    type="button"
                    onClick={onNewsletter}
                    className="inline-flex min-h-11 items-center font-sans text-[10px] font-light tracking-[0.2em] text-cream uppercase transition-colors hover:text-gold"
                >
                    {t('Heritage Letter')}
                </button>

                <AuthMenu />
            </div>
        </div>
    );
}
