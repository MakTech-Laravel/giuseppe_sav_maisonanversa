import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { LanguageSwitcher } from '@/components/maison/shell/language-switcher';
import { useLocale } from '@/hooks/use-locale';
import { activePage, PRIMARY_NAV } from '@/lib/maison-navigation';
import { cn } from '@/lib/utils';

/**
 * The 90px header: wordmark centred above a single centred row of links and
 * the language switcher — matching the brand bar the design shows on every page.
 *
 * The active link is derived from the current URL rather than set by whatever
 * triggered the navigation, so it cannot disagree with the page on screen.
 */
export function SiteNav() {
    const { url } = usePage();
    const { locale } = useLocale();
    const { t } = useTranslation();

    /*
     * The menu belongs to the page it was opened on, so remembering which page
     * that was closes it on arrival anywhere else — nothing has to reach in and
     * reset it after the navigation has already happened.
     */
    const [openedOn, setOpenedOn] = useState<string | null>(null);
    const open = openedOn === url;

    const current = activePage(url, locale);

    return (
        <nav
            className={cn(
                'fixed inset-x-0 top-(--topbar-h) z-199 h-(--nav-h) border-b border-gold/15 bg-choc',
                'flex items-center justify-between gap-3 px-6',
                'md:flex-col md:items-center md:justify-center md:gap-2 md:px-8',
            )}
        >
            <MaisonLink
                to="home"
                data-magnetic
                className="flex items-center gap-2.5 md:flex-col md:items-center md:gap-0.5"
            >
                <PlaceholderImage
                    asset="logo-icon"
                    alt=""
                    captioned={false}
                    className="size-6 shrink-0 md:mb-0.5 md:size-7"
                />

                <span className="font-serif text-[15px] font-medium tracking-[0.2em] text-cream uppercase md:text-[20px] md:leading-none md:tracking-[0.28em]">
                    Maison Anversa
                </span>

                <span className="hidden font-sans text-[8px] font-light tracking-[0.4em] text-gold uppercase md:block">
                    European Heritage Sports and Lifestyle House
                </span>
            </MaisonLink>

            <button
                type="button"
                aria-label={t('Menu')}
                aria-expanded={open}
                aria-controls="maison-nav-links"
                onClick={() => setOpenedOn(open ? null : url)}
                className="flex size-11 shrink-0 flex-col items-center justify-center gap-1.25 md:hidden"
            >
                {[0, 1, 2].map((bar) => (
                    <span
                        key={bar}
                        aria-hidden="true"
                        className={cn(
                            'block h-[1.6px] w-6 bg-gold transition-transform duration-300',
                            open &&
                                bar === 0 &&
                                'translate-y-[6.6px] rotate-45',
                            open && bar === 1 && 'opacity-0',
                            open &&
                                bar === 2 &&
                                'translate-y-[-6.6px] -rotate-45',
                        )}
                    />
                ))}
            </button>

            <div
                id="maison-nav-links"
                className={cn(
                    'absolute top-full left-0 z-10 w-full border-b border-gold/20 bg-choc px-6 pt-1.5 pb-3.5',
                    open ? 'flex flex-col' : 'hidden',
                    'md:static md:flex md:w-auto md:flex-row md:items-center md:gap-8 md:border-0 md:bg-transparent md:p-0',
                )}
            >
                <ul className="flex w-full flex-col md:w-auto md:flex-row md:flex-nowrap md:items-center md:gap-8">
                    {PRIMARY_NAV.map(({ page, label }) => (
                        <li key={page} className="w-full md:w-auto">
                            <MaisonLink
                                to={page}
                                aria-current={
                                    current === page ? 'page' : undefined
                                }
                                // Tapping the page you are already on should still
                                // dismiss the menu, and that fires no navigation.
                                onClick={() => setOpenedOn(null)}
                                className={cn(
                                    'flex min-h-11 w-full items-center border-b border-gold/10 py-4 font-sans text-xs tracking-[0.25em] uppercase transition-colors',
                                    'md:min-h-0 md:w-auto md:border-b md:py-0 md:pb-0.5 md:text-[9px]',
                                    current === page
                                        ? 'border-b-gold text-gold md:border-b-gold'
                                        : 'text-cream/65 hover:text-gold md:border-b-transparent md:hover:border-b-gold',
                                )}
                            >
                                {t(label)}
                            </MaisonLink>
                        </li>
                    ))}
                </ul>

                <div className="mt-3 border-t border-gold/15 pt-3 md:mt-0 md:border-0 md:pt-0">
                    <LanguageSwitcher className="md:gap-1 [&_button]:md:min-h-0 [&_button]:md:min-w-0 [&_button]:md:rounded-sm [&_button]:md:px-1.5 [&_button]:md:py-1 [&_button]:md:text-[9px]" />
                </div>
            </div>
        </nav>
    );
}
