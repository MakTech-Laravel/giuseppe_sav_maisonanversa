import { useTranslation } from 'react-i18next';
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

    return (
        <div className="fixed inset-x-0 top-0 z-[200] flex h-[var(--topbar-h)] items-center justify-between border-b border-gold/20 bg-choc px-6 md:px-12">
            <span className="hidden font-sans text-[10px] font-light tracking-[0.22em] text-cream uppercase md:inline">
                Maison Anversa
            </span>

            <span className="font-serif text-[11px] font-medium tracking-[0.15em] text-gold uppercase md:text-[13px] md:tracking-[0.2em]">
                {t('Eerste Editie — Beperkt tot 100 Stuks')}
            </span>

            <div className="hidden items-center gap-6 md:flex">
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
