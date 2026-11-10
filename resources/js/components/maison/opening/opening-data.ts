import { foundingProductUrl, maisonUrl } from '@/lib/maison-navigation';
import type { MaisonPage } from '@/lib/maison-navigation';
import type { Locale } from '@/types/locale';

/**
 * The right light: Antwerp time (Europe/Brussels), picked before first paint by
 * the inline script in app.blade.php. The copy below only needs the four names.
 */
export type OpeningMood = 'morning' | 'afternoon' | 'evening' | 'night';

export const OPENING_MOODS: readonly OpeningMood[] = [
    'morning',
    'afternoon',
    'evening',
    'night',
];

/** Where a room's window and name lead. */
export type OpeningDestination = { page: MaisonPage } | { product: 'founding' };

export type OpeningRoomText = {
    name: string;
    /** The room's other name: the page it opens. */
    subtitle: string;
};

export type OpeningRoom = {
    numeral: string;
    destination: OpeningDestination;
    /** The window's position over the house artwork, in percent of the SVG box. */
    hotspot: { left: number; top: number; width: number; height: number };
    text: Record<Locale, OpeningRoomText>;
};

/**
 * The eight rooms, in the order the windows light up (I → VIII): ground floor,
 * first floor, then the dormers. Hotspot positions are the reference page's
 * (opening-reference-v3.html). A window and a room name both link straight to the
 * room's page; there is no room view and no room photograph in the opening.
 */
export const OPENING_ROOMS: readonly OpeningRoom[] = [
    {
        numeral: 'I',
        destination: { page: 'home' },
        hotspot: { left: 15.71, top: 66.709, width: 12.84, height: 22.864 },
        text: {
            nl: {
                name: 'Salon',
                subtitle: 'Ontvangst',
            },
            en: {
                name: 'Salon',
                subtitle: 'Reception',
            },
            fr: {
                name: 'Salon',
                subtitle: 'Réception',
            },
        },
    },
    {
        numeral: 'II',
        destination: { page: 'contact' },
        hotspot: { left: 71.903, top: 66.709, width: 12.84, height: 22.864 },
        text: {
            nl: {
                name: 'Bureau',
                subtitle: 'Contact',
            },
            en: {
                name: 'Bureau',
                subtitle: 'Contact',
            },
            fr: {
                name: 'Bureau',
                subtitle: 'Contact',
            },
        },
    },
    {
        numeral: 'III',
        destination: { page: 'circle' },
        hotspot: { left: 15.861, top: 39.824, width: 12.538, height: 18.719 },
        text: {
            nl: {
                name: 'Le Cercle',
                subtitle: 'Founding Circle',
            },
            en: {
                name: 'Le Cercle',
                subtitle: 'Founding Circle',
            },
            fr: {
                name: 'Le Cercle',
                subtitle: 'Founding Circle',
            },
        },
    },
    {
        numeral: 'IV',
        destination: { product: 'founding' },
        hotspot: { left: 43.807, top: 39.824, width: 12.085, height: 19.472 },
        text: {
            nl: {
                name: 'Galerie',
                subtitle: 'Heritage Nº 001',
            },
            en: {
                name: 'Galerie',
                subtitle: 'Heritage Nº 001',
            },
            fr: {
                name: 'Galerie',
                subtitle: 'Heritage Nº 001',
            },
        },
    },
    {
        numeral: 'V',
        destination: { page: 'community' },
        hotspot: { left: 72.054, top: 39.824, width: 12.538, height: 18.719 },
        text: {
            nl: {
                name: 'Salle Commune',
                subtitle: 'Gemeenschap',
            },
            en: {
                name: 'Salle Commune',
                subtitle: 'Community',
            },
            fr: {
                name: 'Salle Commune',
                subtitle: 'Communauté',
            },
        },
    },
    {
        numeral: 'VI',
        destination: { page: 'journal' },
        hotspot: { left: 17.976, top: 17.211, width: 8.157, height: 11.683 },
        text: {
            nl: {
                name: 'Bibliothèque',
                subtitle: 'Journal',
            },
            en: {
                name: 'Bibliothèque',
                subtitle: 'Journal',
            },
            fr: {
                name: 'Bibliothèque',
                subtitle: 'Journal',
            },
        },
    },
    {
        numeral: 'VII',
        destination: { page: 'story' },
        hotspot: { left: 45.166, top: 15.578, width: 9.517, height: 12.06 },
        text: {
            nl: {
                name: 'Atelier',
                subtitle: 'Ons verhaal',
            },
            en: {
                name: 'Atelier',
                subtitle: 'Our Story',
            },
            fr: {
                name: 'Atelier',
                subtitle: 'Notre histoire',
            },
        },
    },
    {
        numeral: 'VIII',
        destination: { page: 'corner' },
        hotspot: { left: 74.169, top: 17.211, width: 8.308, height: 11.683 },
        text: {
            nl: {
                name: 'Club Corner',
                subtitle: 'In jouw club',
            },
            en: {
                name: 'Club Corner',
                subtitle: 'In your club',
            },
            fr: {
                name: 'Club Corner',
                subtitle: 'Dans votre club',
            },
        },
    },
];

/**
 * How many numbers one page of the register holds: the 10 × 10 grid. An edition
 * of this size or smaller is shown whole, with no pager; a larger one is paged.
 */
export const OPENING_PAGE_SIZE = 100;

export type OpeningCopy = {
    greeting: Record<OpeningMood, string>;
    /** `{total}` is the size of the edition, `{n}` the count of numbers still available. */
    limit: string;
    skip: string;
    replay: string;
    registerTitle: string;
    available: string;
    reserved: string;
    /** Proper name — same in every locale. */
    archive: string;
    /** Tooltip on Nº 001. */
    archiveTooltip: string;
    /** The register's page controls, shown only above OPENING_PAGE_SIZE numbers. */
    pagination: string;
    previousPage: string;
    nextPage: string;
    /** `{n}` is the page number. */
    pageLabel: string;
    /** `{from}` and `{to}` are the numbers on this page, `{total}` the edition's size. */
    numbersRange: string;
    /** Button 1: no number picked. */
    chooseNumber: string;
    /** Button 1 with a pick; `{n}` is the number, e.g. 017. */
    claimNumber: string;
    /** Button 1 before launch (purchases paused). */
    joinList: string;
    /** Shown under the grid after a pick; `{n}` is the number. */
    pickedLine: string;
    /** Button 2: the way out to the home page. */
    enterHouse: string;
    tileAvailable: string;
    tileReserved: string;
    tileArchive: string;
    houseHint: string;
    roomsKicker: string;
    dialog: string;
};

/**
 * Opening copy for the three locales, written by the client (final texts, Nov 2026)
 * and used exactly as written: never translated here, never run through t(). NL
 * always "je", FR always "vous". It lives here rather than in the i18n dictionaries
 * because it is a closed set, written per locale, and read by the pre-hydration
 * markup too. At night the evening greeting is used, because a night greeting is a farewell.
 */
export const OPENING_COPY: Record<Locale, OpeningCopy> = {
    nl: {
        greeting: {
            morning: 'Goedemorgen uit Antwerpen',
            afternoon: 'Goedemiddag uit Antwerpen',
            evening: 'Goedenavond uit Antwerpen',
            night: 'Goedenavond uit Antwerpen',
        },
        limit: 'Beperkt tot {total} genummerde rackets · nog {n} nummers vrij',
        skip: 'Overslaan',
        replay: 'Open de deur opnieuw',
        registerTitle: 'Het register',
        available: 'Beschikbaar',
        reserved: 'Gereserveerd',
        archive: 'Maison Archive',
        archiveTooltip: 'Het eigen exemplaar van het huis',
        pagination: 'Pagina’s van het register',
        previousPage: 'Vorige',
        nextPage: 'Volgende',
        pageLabel: 'Pagina {n}',
        numbersRange: 'Nummers {from}–{to} van {total}',
        chooseNumber: 'Kies je nummer',
        claimNumber: 'Reserveer Nº {n}',
        joinList: 'Schrijf je in',
        pickedLine: 'Nº {n} — het jouwe, als je het kiest.',
        enterHouse: 'Kom binnen',
        tileAvailable: 'beschikbaar',
        tileReserved: 'gereserveerd',
        tileArchive: 'Maison Archive',
        houseHint:
            'Elk verlicht raam is een kamer. Kies er een om binnen te stappen.',
        roomsKicker: 'De kamers',
        dialog: 'Welkom bij Maison Anversa',
    },
    en: {
        greeting: {
            morning: 'Good morning from Antwerp',
            afternoon: 'Good afternoon from Antwerp',
            evening: 'Good evening from Antwerp',
            night: 'Good evening from Antwerp',
        },
        limit: 'Limited to {total} numbered rackets · {n} numbers available',
        skip: 'Skip',
        replay: 'Open the door again',
        registerTitle: 'The register',
        available: 'Available',
        reserved: 'Reserved',
        archive: 'Maison Archive',
        archiveTooltip: 'The house’s own piece',
        pagination: 'Register pages',
        previousPage: 'Previous',
        nextPage: 'Next',
        pageLabel: 'Page {n}',
        numbersRange: 'Numbers {from}–{to} of {total}',
        chooseNumber: 'Choose your number',
        claimNumber: 'Claim Nº {n}',
        joinList: 'Join the list',
        pickedLine: 'Nº {n} — yours, if you claim it.',
        enterHouse: 'Enter the house',
        tileAvailable: 'available',
        tileReserved: 'reserved',
        tileArchive: 'Maison Archive',
        houseHint: 'Every lit window is a room. Choose one to step inside.',
        roomsKicker: 'The rooms',
        dialog: 'Welcome to Maison Anversa',
    },
    fr: {
        greeting: {
            morning: 'Bonjour d’Anvers',
            afternoon: 'Bonjour d’Anvers',
            evening: 'Bonsoir d’Anvers',
            night: 'Bonsoir d’Anvers',
        },
        limit: 'Édition limitée à {total} raquettes numérotées · encore {n} numéros disponibles',
        skip: 'Passer',
        replay: 'Rouvrir la porte',
        registerTitle: 'Le registre',
        available: 'Disponible',
        reserved: 'Réservé',
        archive: 'Maison Archive',
        archiveTooltip: 'La pièce de la Maison',
        pagination: 'Pages du registre',
        previousPage: 'Précédent',
        nextPage: 'Suivant',
        pageLabel: 'Page {n}',
        numbersRange: 'Numéros {from}–{to} sur {total}',
        chooseNumber: 'Choisissez votre numéro',
        claimNumber: 'Réserver le Nº {n}',
        joinList: 'M’inscrire',
        pickedLine: 'Nº {n} — le vôtre, si vous le réservez.',
        enterHouse: 'Entrer dans la maison',
        tileAvailable: 'disponible',
        tileReserved: 'réservé',
        tileArchive: 'Maison Archive',
        houseHint:
            'Chaque fenêtre éclairée est une pièce. Choisissez-en une pour entrer.',
        roomsKicker: 'Les pièces',
        dialog: 'Bienvenue chez Maison Anversa',
    },
};

/** A room's name and label, for the pages that show the house too (/huis). */
export function roomText(locale: Locale, numeral: string): OpeningRoomText {
    const room = OPENING_ROOMS.find((entry) => entry.numeral === numeral);

    if (room === undefined) {
        throw new Error(`Unknown room ${numeral}`);
    }

    return room.text[locale];
}

/** The URL a room's window and list row link to, e.g. /en/journal. */
export function roomHref(room: OpeningRoom, locale: Locale): string {
    return 'page' in room.destination
        ? maisonUrl(room.destination.page, locale)
        : foundingProductUrl(locale);
}

export function fillCopy(
    template: string,
    values: Record<string, string | number>,
): string {
    return Object.entries(values).reduce(
        (text, [key, value]) => text.replaceAll(`{${key}}`, String(value)),
        template,
    );
}

export const OPENING_SEEN_KEY = 'maison.opening.seen';
