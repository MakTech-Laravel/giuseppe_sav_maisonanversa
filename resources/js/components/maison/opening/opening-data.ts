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

/** Where a room's "Enter …" button goes. */
export type OpeningDestination = { page: MaisonPage } | { product: 'founding' };

export type OpeningRoomText = {
    name: string;
    /** The room's other name: the page it opens. */
    subtitle: string;
    line: string;
    /** The whole button label, e.g. "Enter the Salon →". */
    enter: string;
};

export type OpeningRoom = {
    numeral: string;
    destination: OpeningDestination;
    /** Photo base name under /images/optimized/rooms/, or null: the frame stays empty. */
    photo: string | null;
    /** The window's position over the house artwork, in percent of the SVG box. */
    hotspot: { left: number; top: number; width: number; height: number };
    text: Record<Locale, OpeningRoomText>;
};

/**
 * The eight rooms, in the order the windows light up (I → VIII): ground floor,
 * first floor, then the dormers. Hotspot positions are the reference page's.
 *
 * Office, Le Cercle and Gallery have no room photograph yet, so their card keeps
 * an empty frame until the client supplies one.
 */
export const OPENING_ROOMS: readonly OpeningRoom[] = [
    {
        numeral: 'I',
        destination: { page: 'home' },
        photo: 'room-entrance',
        hotspot: { left: 15.71, top: 66.709, width: 12.236, height: 22.362 },
        text: {
            nl: {
                name: 'Salon',
                subtitle: 'Ontvangst',
                line: 'Waar elk bezoek begint.',
                enter: 'Stap binnen →',
            },
            en: {
                name: 'Salon',
                subtitle: 'Reception',
                line: 'Where every visit begins.',
                enter: 'Step inside →',
            },
            fr: {
                name: 'Salon',
                subtitle: 'Réception',
                line: 'Là où commence chaque visite.',
                enter: 'Entrer →',
            },
        },
    },
    {
        numeral: 'II',
        destination: { page: 'contact' },
        photo: null,
        hotspot: { left: 71.903, top: 66.709, width: 12.236, height: 22.362 },
        text: {
            nl: {
                name: 'Bureau',
                subtitle: 'Contact',
                line: 'Vragen, bestellingen en onderhoud.',
                enter: 'Stap binnen →',
            },
            en: {
                name: 'Bureau',
                subtitle: 'Contact',
                line: 'Questions, orders and care.',
                enter: 'Step inside →',
            },
            fr: {
                name: 'Bureau',
                subtitle: 'Contact',
                line: 'Questions, commandes et entretien.',
                enter: 'Entrer →',
            },
        },
    },
    {
        numeral: 'III',
        destination: { page: 'circle' },
        photo: null,
        hotspot: { left: 15.861, top: 39.824, width: 11.934, height: 18.216 },
        text: {
            nl: {
                name: 'Le Cercle',
                subtitle: 'Founding Circle',
                line: 'De eerste honderd, en wat hen verbindt.',
                enter: 'Stap binnen →',
            },
            en: {
                name: 'Le Cercle',
                subtitle: 'Founding Circle',
                line: 'The first hundred, and what they share.',
                enter: 'Step inside →',
            },
            fr: {
                name: 'Le Cercle',
                subtitle: 'Founding Circle',
                line: 'Les cent premiers, et ce qui les unit.',
                enter: 'Entrer →',
            },
        },
    },
    {
        numeral: 'IV',
        destination: { product: 'founding' },
        photo: null,
        hotspot: { left: 43.807, top: 39.824, width: 11.48, height: 18.97 },
        text: {
            nl: {
                name: 'Galerie',
                subtitle: 'Heritage Nº 001',
                line: 'Het eerste racket van het huis.',
                enter: 'Stap binnen →',
            },
            en: {
                name: 'Galerie',
                subtitle: 'Heritage Nº 001',
                line: 'The first racket of the house.',
                enter: 'Step inside →',
            },
            fr: {
                name: 'Galerie',
                subtitle: 'Heritage Nº 001',
                line: 'La première raquette de la Maison.',
                enter: 'Entrer →',
            },
        },
    },
    {
        numeral: 'V',
        destination: { page: 'community' },
        photo: 'room-coffee',
        hotspot: { left: 72.054, top: 39.824, width: 11.934, height: 18.216 },
        text: {
            nl: {
                name: 'Salle Commune',
                subtitle: 'Gemeenschap',
                line: 'Sessies, clubs en de mensen die spelen.',
                enter: 'Stap binnen →',
            },
            en: {
                name: 'Salle Commune',
                subtitle: 'Community',
                line: 'Sessions, clubs and the people who play.',
                enter: 'Step inside →',
            },
            fr: {
                name: 'Salle Commune',
                subtitle: 'Communauté',
                line: 'Sessions, clubs et ceux qui jouent.',
                enter: 'Entrer →',
            },
        },
    },
    {
        numeral: 'VI',
        destination: { page: 'journal' },
        photo: 'room-library',
        hotspot: { left: 17.976, top: 17.211, width: 7.553, height: 11.181 },
        text: {
            nl: {
                name: 'Bibliothèque',
                subtitle: 'Journal',
                line: 'Verhalen over sport, cultuur en vakmanschap.',
                enter: 'Stap binnen →',
            },
            en: {
                name: 'Bibliothèque',
                subtitle: 'Journal',
                line: 'Stories about sport, culture and craft.',
                enter: 'Step inside →',
            },
            fr: {
                name: 'Bibliothèque',
                subtitle: 'Journal',
                line: 'Récits de sport, de culture et de savoir-faire.',
                enter: 'Entrer →',
            },
        },
    },
    {
        numeral: 'VII',
        destination: { page: 'story' },
        photo: 'room-atelier',
        hotspot: { left: 45.166, top: 15.578, width: 8.912, height: 11.558 },
        text: {
            nl: {
                name: 'Atelier',
                subtitle: 'Ons verhaal',
                line: 'Hoe het huis begon.',
                enter: 'Stap binnen →',
            },
            en: {
                name: 'Atelier',
                subtitle: 'Our Story',
                line: 'How the house began.',
                enter: 'Step inside →',
            },
            fr: {
                name: 'Atelier',
                subtitle: 'Notre histoire',
                line: 'Comment la Maison est née.',
                enter: 'Entrer →',
            },
        },
    },
    {
        numeral: 'VIII',
        destination: { page: 'corner' },
        photo: 'room-courtyard',
        hotspot: { left: 74.169, top: 17.211, width: 7.704, height: 11.181 },
        text: {
            nl: {
                name: 'Club Corner',
                subtitle: 'In jouw club',
                line: 'Maison Anversa in jouw padelclub.',
                enter: 'Stap binnen →',
            },
            en: {
                name: 'Club Corner',
                subtitle: 'In your club',
                line: 'Maison Anversa in your padel club.',
                enter: 'Step inside →',
            },
            fr: {
                name: 'Club Corner',
                subtitle: 'Dans votre club',
                line: 'Maison Anversa dans votre club de padel.',
                enter: 'Entrer →',
            },
        },
    },
];

/** Room photos exist as AVIF/WebP at 768 px; the original PNGs are never requested. */
export function openingPhotoSources(photo: string): {
    avif: string;
    webp: string;
} {
    const base = `/images/optimized/rooms/${photo}-768`;

    return { avif: `${base}.avif`, webp: `${base}.webp` };
}

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
    /** `{n}` is the room's Roman numeral. */
    roomKicker: string;
    back: string;
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
        roomKicker: 'Kamer {n} van VIII',
        back: '← Terug naar het huis',
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
        roomKicker: 'Room {n} of VIII',
        back: '← Back to the house',
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
        roomKicker: 'Pièce {n} sur VIII',
        back: '← Retour à la Maison',
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
