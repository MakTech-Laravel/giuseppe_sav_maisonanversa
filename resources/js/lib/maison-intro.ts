import type { ImageAssetName } from '@/lib/imagery';
import type { MaisonPage } from '@/lib/maison-navigation';
import type { Locale } from '@/types/locale';

/**
 * The copy for one room, in one language.
 *
 * The name is split rather than stored as a string with `<em>` in it, because
 * the emphasised word moves: "De <em>Bibliotheek</em>" but "L'<em>Atelier</em>".
 * Splitting keeps the markup in JSX, so nothing has to parse HTML out of data.
 */
type RoomCopy = {
    eyebrow: string;
    opening: string;
    emphasis: string;
    subtitle: string;
};

export type IntroSlide = {
    /** The photograph behind the room. */
    room: ImageAssetName;
    /**
     * Where "enter this room" leads. The entrance hall is an arrival rather
     * than a room, so it offers nothing to enter.
     */
    destination: MaisonPage | null;
    copy: Record<Locale, RoomCopy>;
};

/*
 * These seven live here rather than in `lang/*.json` because that is where the
 * prototype kept them too: the dictionary was built by walking the page's text
 * nodes, and the slide copy was never in the page — it was assembled in script
 * from an array carrying its own `_en` and `_fr` fields.
 */
export const INTRO_SLIDES: readonly IntroSlide[] = [
    {
        room: 'room-entrance',
        destination: null,
        copy: {
            nl: {
                eyebrow: 'Entreehal',
                opening: 'Welkom in het ',
                emphasis: 'Huis',
                subtitle: 'Een erfgoed van Antwerpen',
            },
            en: {
                eyebrow: 'Entrance Hall',
                opening: 'Welcome to the ',
                emphasis: 'House',
                subtitle: 'A heritage from Antwerp',
            },
            fr: {
                eyebrow: "Hall d'Entrée",
                opening: 'Bienvenue dans la ',
                emphasis: 'Maison',
                subtitle: "Un héritage d'Anvers",
            },
        },
    },
    {
        room: 'room-library',
        destination: 'story',
        copy: {
            nl: {
                eyebrow: 'Kamer I',
                opening: 'De ',
                emphasis: 'Bibliotheek',
                subtitle: 'Ons verhaal & filosofie',
            },
            en: {
                eyebrow: 'Room I',
                opening: 'The ',
                emphasis: 'Library',
                subtitle: 'Our story & philosophy',
            },
            fr: {
                eyebrow: 'Salle I',
                opening: 'La ',
                emphasis: 'Bibliothèque',
                subtitle: 'Notre histoire & philosophie',
            },
        },
    },
    {
        room: 'room-atelier',
        destination: 'product',
        copy: {
            nl: {
                eyebrow: 'Kamer II',
                opening: 'Het ',
                emphasis: 'Atelier',
                subtitle: 'Heritage No.001',
            },
            en: {
                eyebrow: 'Room II',
                opening: 'The ',
                emphasis: 'Atelier',
                subtitle: 'Heritage No.001',
            },
            fr: {
                eyebrow: 'Salle II',
                opening: "L'",
                emphasis: 'Atelier',
                subtitle: 'Heritage No.001',
            },
        },
    },
    {
        room: 'room-dressing',
        destination: 'dressing',
        copy: {
            nl: {
                eyebrow: 'Kamer III',
                opening: 'De ',
                emphasis: 'Kleedkamer',
                subtitle: 'Activewear & accessoires',
            },
            en: {
                eyebrow: 'Room III',
                opening: 'The ',
                emphasis: 'Dressing Room',
                subtitle: 'Activewear & accessories',
            },
            fr: {
                eyebrow: 'Salle III',
                opening: 'Le ',
                emphasis: 'Vestiaire',
                subtitle: 'Vêtements de sport & accessoires',
            },
        },
    },
    {
        room: 'room-coffee',
        destination: 'journal',
        copy: {
            nl: {
                eyebrow: 'Kamer IV',
                opening: 'De ',
                emphasis: 'Koffiekamer',
                subtitle: 'Rituelen & community',
            },
            en: {
                eyebrow: 'Room IV',
                opening: 'The ',
                emphasis: 'Coffee Room',
                subtitle: 'Rituals & community',
            },
            fr: {
                eyebrow: 'Salle IV',
                opening: 'Le ',
                emphasis: 'Salon de Café',
                subtitle: 'Rituels & communauté',
            },
        },
    },
    {
        room: 'room-courtyard',
        destination: 'corner',
        copy: {
            nl: {
                eyebrow: 'Kamer V',
                opening: 'De ',
                emphasis: 'Binnenhof',
                subtitle: 'Padel & evenementen',
            },
            en: {
                eyebrow: 'Room V',
                opening: 'The ',
                emphasis: 'Courtyard',
                subtitle: 'Padel & events',
            },
            fr: {
                eyebrow: 'Salle V',
                opening: 'La ',
                emphasis: 'Cour Intérieure',
                subtitle: 'Padel & événements',
            },
        },
    },
    {
        /* The closing card stays in the courtyard, as the prototype does. */
        room: 'room-courtyard',
        destination: 'home',
        copy: {
            nl: {
                eyebrow: 'De Maison',
                opening: 'Betreed ',
                emphasis: 'Maison Anversa',
                subtitle: 'Het digitale vlaggenschip',
            },
            en: {
                eyebrow: 'The Maison',
                opening: 'Enter ',
                emphasis: 'Maison Anversa',
                subtitle: 'The digital flagship',
            },
            fr: {
                eyebrow: 'La Maison',
                opening: 'Entrer dans ',
                emphasis: 'Maison Anversa',
                subtitle: 'Le vaisseau amiral numérique',
            },
        },
    },
];

/** The rooms, excluding the closing card, which the counter numbers against. */
export const ROOM_COUNT = INTRO_SLIDES.length - 1;

/** The index of the closing card. */
export const CLOSING_SLIDE = INTRO_SLIDES.length - 1;

/** The photographs to stack behind the intro: six, for seven slides. */
export const INTRO_ROOMS: readonly ImageAssetName[] = [
    ...new Set(INTRO_SLIDES.map((slide) => slide.room)),
];

/** Which photograph a slide is standing in front of. */
export function introPanel(slide: number): number {
    return INTRO_ROOMS.indexOf((INTRO_SLIDES[slide] ?? INTRO_SLIDES[0]).room);
}

export function introCopy(slide: number, locale: Locale): RoomCopy {
    const entry = INTRO_SLIDES[slide] ?? INTRO_SLIDES[0];

    return entry.copy[locale] ?? entry.copy.nl;
}
