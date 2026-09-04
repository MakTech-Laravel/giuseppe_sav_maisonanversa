import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { AuthMenu } from '@/components/maison/shell/auth-menu';
import { LanguageSwitcher } from '@/components/maison/shell/language-switcher';
import { useLocale } from '@/hooks/use-locale';
import { activePage, PRIMARY_NAV } from '@/lib/maison-navigation';
import { cn } from '@/lib/utils';

/**
 * The 90px header: wordmark centred above a single centred row of links and
 * the language switcher — matching the brand bar the design shows on every page.
 *
 * Horizontal links need the 1100px maison breakpoint. At 768–1024 the nine
 * labels plus language controls are wider than the viewport and were clipped.
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
                'ma-lg:flex-col ma-lg:items-center ma-lg:justify-center ma-lg:gap-2 ma-lg:px-8',
            )}
        >
            <MaisonLink
                to="home"
                data-magnetic
                className="flex min-w-0 items-center gap-2.5 ma-lg:flex-col ma-lg:items-center ma-lg:gap-0.5"
            >
                <PlaceholderImage
                    asset="logo-icon"
                    alt=""
                    captioned={false}
                    className="size-6 shrink-0 ma-lg:mb-0.5 ma-lg:size-7"
                />

                <span className="truncate font-serif text-[15px] font-medium tracking-[0.2em] text-cream uppercase ma-lg:text-[20px] ma-lg:leading-none ma-lg:tracking-[0.28em]">
                    Maison Anversa
                </span>

                <span className="hidden font-sans text-[8px] font-light tracking-[0.4em] text-gold uppercase ma-lg:block">
                    European Heritage Sports and Lifestyle House
                </span>
            </MaisonLink>

            <div className="flex shrink-0 items-center gap-1 ma-lg:hidden">
                <AuthMenu compact />
                <button
                    type="button"
                    aria-label={t('Menu')}
                    aria-expanded={open}
                    aria-controls="maison-nav-links"
                    onClick={() => setOpenedOn(open ? null : url)}
                    className="flex size-11 shrink-0 flex-col items-center justify-center gap-1.25"
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
            </div>

            <div
                id="maison-nav-links"
                className={cn(
                    'absolute top-full left-0 z-10 w-full border-b border-gold/20 bg-choc px-6 pt-1.5 pb-3.5',
                    open ? 'flex flex-col' : 'hidden',
                    'ma-lg:static ma-lg:flex ma-lg:w-auto ma-lg:max-w-full ma-lg:flex-row ma-lg:items-center ma-lg:gap-5 ma-lg:border-0 ma-lg:bg-transparent ma-lg:p-0',
                )}
            >
                <ul className="flex w-full flex-col ma-lg:w-auto ma-lg:flex-row ma-lg:flex-nowrap ma-lg:items-center ma-lg:gap-5">
                    {PRIMARY_NAV.map(({ page, label }) => (
                        <li key={page} className="w-full ma-lg:w-auto">
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
                                    'ma-lg:min-h-0 ma-lg:w-auto ma-lg:border-b ma-lg:py-0 ma-lg:pb-0.5 ma-lg:text-[9px] ma-lg:tracking-[0.18em]',
                                    current === page
                                        ? 'border-b-gold text-gold ma-lg:border-b-gold'
                                        : 'text-cream/65 hover:text-gold ma-lg:border-b-transparent ma-lg:hover:border-b-gold',
                                )}
                            >
                                {t(label)}
                            </MaisonLink>
                        </li>
                    ))}
                </ul>

                <div className="mt-3 flex items-center gap-4 border-t border-gold/15 pt-3 ma-lg:mt-0 ma-lg:ml-2 ma-lg:border-t-0 ma-lg:border-l ma-lg:pt-0 ma-lg:pl-4">
                    <LanguageSwitcher className="ma-lg:gap-1 [&_button]:ma-lg:min-h-0 [&_button]:ma-lg:min-w-0 [&_button]:ma-lg:rounded-sm [&_button]:ma-lg:px-1.5 [&_button]:ma-lg:py-1 [&_button]:ma-lg:text-[9px]" />
                </div>
            </div>
        </nav>
    );
}
