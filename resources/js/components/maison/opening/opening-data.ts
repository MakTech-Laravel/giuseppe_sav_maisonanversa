import type { MaisonPage } from '@/lib/maison-navigation';
import type { Locale } from '@/types/locale';

/**
 * The right light: the visitor's local time, picked before first paint by the
 * inline script in app.blade.php. The copy below only needs the four names.
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
                enter: 'Betreed de Salon →',
            },
            en: {
                name: 'Salon',
                subtitle: 'Reception',
                line: 'Where every visit begins.',
                enter: 'Enter the Salon →',
            },
            fr: {
                name: 'Salon',
                subtitle: 'Réception',
                line: 'Là où chaque visite commence.',
                enter: 'Entrer dans le Salon →',
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
                line: 'Vragen, zorg en afspraken.',
                enter: 'Betreed het Bureau →',
            },
            en: {
                name: 'Office',
                subtitle: 'Contact',
                line: 'Questions, care and appointments.',
                enter: 'Enter the Office →',
            },
            fr: {
                name: 'Bureau',
                subtitle: 'Contact',
                line: 'Questions, soins et rendez-vous.',
                enter: 'Entrer dans le Bureau →',
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
                line: 'De eerste honderd, en wat zij delen.',
                enter: 'Betreed Le Cercle →',
            },
            en: {
                name: 'Le Cercle',
                subtitle: 'Founding Circle',
                line: 'The first hundred, and what they share.',
                enter: 'Enter Le Cercle →',
            },
            fr: {
                name: 'Le Cercle',
                subtitle: 'Founding Circle',
                line: 'Les cent premiers, et ce qu’ils partagent.',
                enter: 'Entrer dans Le Cercle →',
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
                enter: 'Betreed de Galerie →',
            },
            en: {
                name: 'Gallery',
                subtitle: 'Heritage Nº 001',
                line: 'The first racket of the house.',
                enter: 'Enter the Gallery →',
            },
            fr: {
                name: 'Galerie',
                subtitle: 'Heritage Nº 001',
                line: 'La première raquette de la maison.',
                enter: 'Entrer dans la Galerie →',
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
                subtitle: 'Community',
                line: 'Sessies, clubs en de mensen die spelen.',
                enter: 'Betreed de Salle Commune →',
            },
            en: {
                name: 'Salle Commune',
                subtitle: 'Community',
                line: 'Sessions, clubs and the people who play.',
                enter: 'Enter the Salle Commune →',
            },
            fr: {
                name: 'Salle Commune',
                subtitle: 'Communauté',
                line: 'Sessions, clubs et ceux qui jouent.',
                enter: 'Entrer dans la Salle Commune →',
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
                name: 'Bibliotheek',
                subtitle: 'Journal',
                line: 'Verhalen over sport, cultuur en vakmanschap.',
                enter: 'Betreed de Bibliotheek →',
            },
            en: {
                name: 'Library',
                subtitle: 'Journal',
                line: 'Stories about sport, culture and craft.',
                enter: 'Enter the Library →',
            },
            fr: {
                name: 'Bibliothèque',
                subtitle: 'Journal',
                line: 'Récits de sport, de culture et d’artisanat.',
                enter: 'Entrer dans la Bibliothèque →',
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
                enter: 'Betreed het Atelier →',
            },
            en: {
                name: 'Atelier',
                subtitle: 'Our Story',
                line: 'How the house began.',
                enter: 'Enter the Atelier →',
            },
            fr: {
                name: 'Atelier',
                subtitle: 'Notre histoire',
                line: 'Comment la maison est née.',
                enter: 'Entrer dans l’Atelier →',
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
                subtitle: 'De speelkamer',
                line: 'Maison Anversa in uw padelclub.',
                enter: 'Betreed Club Corner →',
            },
            en: {
                name: 'Club Corner',
                subtitle: 'The Playroom',
                line: 'Maison Anversa in your padel club.',
                enter: 'Enter Club Corner →',
            },
            fr: {
                name: 'Club Corner',
                subtitle: 'La salle de jeux',
                line: 'Maison Anversa dans votre club de padel.',
                enter: 'Entrer dans Club Corner →',
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
    replay: string;
    registerTitle: string;
    available: string;
    reserved: string;
    /** The register's page controls, shown only above OPENING_PAGE_SIZE numbers. */
    pagination: string;
    previousPage: string;
    nextPage: string;
    /** `{n}` is the page number. */
    pageLabel: string;
    /** `{from}` and `{to}` are the numbers on this page, `{total}` the edition's size. */
    numbersRange: string;
    chooseNumber: string;
    /** The way out to the home page. */
    enterHouse: string;
    tileAvailable: string;
    tileReserved: string;
    houseHint: string;
    roomsKicker: string;
    /** `{n}` is the numeral, `{sub}` the room's subtitle. */
    roomKicker: string;
    allRooms: string;
    dialog: string;
};

/**
 * Opening copy for the three locales. It lives here rather than in the i18n
 * dictionaries for the same reason the old intro's did: it is a closed set,
 * written per locale, and read by the pre-hydration markup too.
 */
export const OPENING_COPY: Record<Locale, OpeningCopy> = {
    nl: {
        greeting: {
            morning: 'Goedemorgen vanuit Antwerpen',
            afternoon: 'Goedemiddag vanuit Antwerpen',
            evening: 'Goedenavond vanuit Antwerpen',
            night: 'Goedenacht vanuit Antwerpen',
        },
        limit: 'Beperkt tot {total} genummerde rackets · nog {n} nummers vrij',
        replay: 'Open de deur opnieuw',
        registerTitle: 'Het register',
        available: 'Beschikbaar',
        reserved: 'Gereserveerd',
        pagination: 'Pagina’s van het register',
        previousPage: 'Vorige',
        nextPage: 'Volgende',
        pageLabel: 'Pagina {n}',
        numbersRange: 'Nummers {from}–{to} van {total}',
        chooseNumber: 'Kies uw nummer',
        enterHouse: 'Betreed het huis',
        tileAvailable: 'beschikbaar',
        tileReserved: 'gereserveerd',
        houseHint:
            'Elk verlicht raam is een kamer. Tik op een raam om binnen te stappen.',
        roomsKicker: 'De kamers',
        roomKicker: 'Kamer {n} · {sub}',
        allRooms: 'Alle kamers',
        dialog: 'Welkom bij Maison Anversa',
    },
    en: {
        greeting: {
            morning: 'Good morning from Antwerp',
            afternoon: 'Good afternoon from Antwerp',
            evening: 'Good evening from Antwerp',
            night: 'Good night from Antwerp',
        },
        limit: 'Limited to {total} numbered rackets · {n} numbers available',
        replay: 'Open the door again',
        registerTitle: 'The register',
        available: 'Available',
        reserved: 'Reserved',
        pagination: 'Register pages',
        previousPage: 'Previous',
        nextPage: 'Next',
        pageLabel: 'Page {n}',
        numbersRange: 'Numbers {from}–{to} of {total}',
        chooseNumber: 'Choose your number',
        enterHouse: 'Enter the house',
        tileAvailable: 'available',
        tileReserved: 'reserved',
        houseHint: 'Every lit window is a room. Tap one to step inside.',
        roomsKicker: 'The rooms',
        roomKicker: 'Room {n} · {sub}',
        allRooms: 'All rooms',
        dialog: 'Welcome to Maison Anversa',
    },
    fr: {
        greeting: {
            morning: 'Bonjour depuis Anvers',
            afternoon: 'Bon après-midi depuis Anvers',
            evening: 'Bonsoir depuis Anvers',
            night: 'Bonne nuit depuis Anvers',
        },
        limit: 'Limitée à {total} raquettes numérotées · {n} numéros disponibles',
        replay: 'Rouvrir la porte',
        registerTitle: 'Le registre',
        available: 'Disponible',
        reserved: 'Réservé',
        pagination: 'Pages du registre',
        previousPage: 'Précédent',
        nextPage: 'Suivant',
        pageLabel: 'Page {n}',
        numbersRange: 'Numéros {from}–{to} sur {total}',
        chooseNumber: 'Choisissez votre numéro',
        enterHouse: 'Entrer dans la maison',
        tileAvailable: 'disponible',
        tileReserved: 'réservé',
        houseHint:
            'Chaque fenêtre éclairée est une pièce. Touchez-en une pour entrer.',
        roomsKicker: 'Les pièces',
        roomKicker: 'Pièce {n} · {sub}',
        allRooms: 'Toutes les pièces',
        dialog: 'Bienvenue chez Maison Anversa',
    },
};

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
