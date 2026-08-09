import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { LanguageSwitcher } from '@/components/maison/shell/language-switcher';
import { useLocale } from '@/hooks/use-locale';
import { activePage, PRIMARY_NAV } from '@/lib/maison-navigation';
import { cn } from '@/lib/utils';

/**
 * The 90px header: stacked wordmark above the links on a wide screen, a single
 * row with a hamburger below 768px.
 *
 * The active link is derived from the current URL rather than set by whatever
 * triggered the navigation, so it cannot disagree with the page on screen.
 */
export function SiteNav() {
    const { url } = usePage();
    const { locale } = useLocale();
    const { t } = useTranslation();
    const [scrolled, setScrolled] = useState(false);

    /*
     * The menu belongs to the page it was opened on, so remembering which page
     * that was closes it on arrival anywhere else — nothing has to reach in and
     * reset it after the navigation has already happened.
     */
    const [openedOn, setOpenedOn] = useState<string | null>(null);
    const open = openedOn === url;

    const current = activePage(url, locale);

    /*
     * The header sits over the page rather than above it, so it deepens slightly
     * once content has begun to slide underneath.
     */
    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 40);

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <nav
            className={cn(
                'fixed inset-x-0 top-(--topbar-h) z-199 border-b border-gold/12 backdrop-blur-xl transition-colors duration-300',
                scrolled ? 'bg-choc/99' : 'bg-choc/96',
                'flex h-(--nav-h) items-center justify-between gap-3 px-6',
                'md:h-(--nav-h) md:flex-col md:justify-center md:gap-2 md:px-12',
            )}
        >
            <MaisonLink
                to="home"
                data-magnetic
                className="flex items-center gap-2.5 md:flex-col md:gap-0.5"
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
                className="flex size-11 flex-col items-center justify-center gap-1.25 md:hidden"
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

            <ul
                id="maison-nav-links"
                className={cn(
                    'absolute top-full left-0 w-full flex-col border-b border-gold/20 bg-choc/99 px-6 pt-1.5 pb-3.5',
                    open ? 'flex' : 'hidden',
                    'md:static md:w-auto md:flex-row md:gap-8 md:border-0 md:bg-transparent md:p-0',
                )}
            >
                {PRIMARY_NAV.map(({ page, label }) => (
                    <li key={page} className="w-full md:w-auto">
                        <MaisonLink
                            to={page}
                            aria-current={current === page ? 'page' : undefined}
                            // Tapping the page you are already on should still
                            // dismiss the menu, and that fires no navigation.
                            onClick={() => setOpenedOn(null)}
                            className={cn(
                                'block border-b border-gold/10 py-4 font-sans text-xs tracking-[0.25em] uppercase transition-colors',
                                'md:border-b md:pb-0.5 md:text-[9px]',
                                current === page
                                    ? 'border-b-gold text-gold md:border-b-gold'
                                    : 'text-cream/65 hover:text-gold md:border-b-transparent md:hover:border-b-gold',
                            )}
                        >
                            {t(label)}
                        </MaisonLink>
                    </li>
                ))}

                <li className="mt-3 md:mt-0 md:ml-4">
                    <LanguageSwitcher />
                </li>
            </ul>
        </nav>
    );
}
