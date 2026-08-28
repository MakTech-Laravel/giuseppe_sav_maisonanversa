import * as maison from '@/routes/maison';
import type { Locale } from '@/types/locale';

/**
 * The fourteen public pages, named as their routes are. Labels are the Dutch
 * source copy, so `t(label)` resolves the translation and falls back to this
 * exact text when there is none.
 */
export const MAISON_PAGES = [
    'home',
    'house',
    'products',
    'story',
    'circle',
    'dressing',
    'journal',
    'community',
    'corner',
    'contact',
    'privacy',
    'terms',
    'shipping',
    'care',
] as const;

export type MaisonPage = (typeof MAISON_PAGES)[number];

export function maisonUrl(page: MaisonPage, locale: Locale): string {
    return maison[page].url(locale);
}

/** Heritage No.001's product slug — the founding SKU, always shown first in the catalog. */
export const FOUNDING_PRODUCT_SLUG = 'heritage-no-001';

/** For CTAs that specifically mean "go look at Heritage No.001", not "browse products". */
export function foundingProductUrl(locale: Locale): string {
    return `${maisonUrl('products', locale)}/${FOUNDING_PRODUCT_SLUG}`;
}

export type NavItem = { page: MaisonPage; label: string; hash?: string };

/** The nine links in the header, in the prototype's order. */
export const PRIMARY_NAV: readonly NavItem[] = [
    { page: 'house', label: 'Het Huis' },
    { page: 'products', label: 'Producten' },
    { page: 'story', label: 'Ons Verhaal' },
    { page: 'circle', label: 'Founding Circle' },
    { page: 'dressing', label: 'Kleedkamer' },
    { page: 'journal', label: 'Journal' },
    { page: 'community', label: 'Community' },
    { page: 'corner', label: 'Club Corner' },
    { page: 'contact', label: 'Contact' },
];

export type FooterChannel = 'instagram' | 'press';

export type FooterExternalItem = {
    href: string;
    label: string;
    channel?: FooterChannel;
};

export type FooterColumn = {
    heading: string;
    items: readonly (NavItem | FooterExternalItem)[];
};

export const FOOTER_COLUMNS: readonly FooterColumn[] = [
    {
        heading: 'Verkennen',
        items: [
            { page: 'products', label: 'Producten' },
            { page: 'story', label: 'Ons Verhaal' },
            { page: 'circle', label: 'Founding Circle' },
            { page: 'dressing', label: 'Kleedkamer' },
            { page: 'journal', label: 'Journal' },
            { page: 'community', label: 'Community' },
            { page: 'corner', label: 'Club Corner' },
        ],
    },
    {
        heading: 'Support',
        items: [
            { page: 'contact', label: 'Contact' },
            { page: 'shipping', label: 'Verzending & Retour' },
            { page: 'care', label: 'Zorg & Garantie' },
            { page: 'contact', label: 'FAQ', hash: 'faq' },
            { page: 'privacy', label: 'Privacybeleid' },
            { page: 'terms', label: 'Algemene voorwaarden' },
        ],
    },
    {
        heading: 'Volg Ons',
        items: [
            {
                href: 'https://www.instagram.com/',
                label: 'Instagram',
                channel: 'instagram',
            },
            { href: '#heritage-letter', label: 'Heritage Letter' },
            {
                href: 'mailto:press@maisonanversa.com',
                label: 'Pers',
                channel: 'press',
            },
        ],
    },
];

export function isNavItem(
    item: NavItem | FooterExternalItem,
): item is NavItem {
    return 'page' in item;
}

/**
 * Which nav entry a URL belongs to, used for the active state.
 *
 * Derived from the path rather than tracked in state, which is how the
 * prototype's `#nav-dressing` came to have no active state at all: its
 * `showPage()` simply forgot to list it.
 */
export function activePage(url: string, locale: Locale): MaisonPage | null {
    const path = url.split(/[?#]/)[0].replace(/\/+$/, '');

    const ranked = [...MAISON_PAGES].sort(
        (a, b) =>
            maisonUrl(b, locale).length - maisonUrl(a, locale).length,
    );

    return (
        ranked.find((page) => {
            const pagePath = maisonUrl(page, locale).replace(/\/+$/, '');

            if (page === 'home') {
                return path === pagePath;
            }

            return path === pagePath || path.startsWith(`${pagePath}/`);
        }) ?? null
    );
}
