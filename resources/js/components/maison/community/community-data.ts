import type { CommunityTab } from '@/components/maison/community/community-tabs';

export type FeedPostData = {
    id: string;
    /** User-authored posts render content as escaped React text, not via t(). */
    userAuthored?: boolean;
    official?: boolean;
    initials?: string;
    avatarBg?: string;
    name: string;
    info: string;
    badge: string;
    badgeOfficial?: boolean;
    content: string;
    imageLabel?: string;
    likes: number;
    comments: number;
};

export type CourtPin = {
    id: string;
    top: string;
    left: string;
    label: string;
    coming?: boolean;
};

export type CourtCardData = {
    id: string;
    name: string;
    status: string;
    statusComing?: boolean;
    location: string;
    stats: { num: string; label: string }[];
    tags: string[];
    tagsMuted?: boolean;
};

export type SessionCardData = {
    id: string;
    title: string;
    spots: string;
    meta: string[];
    players: string[];
    emptySlots: number;
};

export type EventCardData = {
    id: string;
    imageVariant: 1 | 2 | 3;
    imageLabel: string;
    date: string;
    title: string;
    location: string;
    attendees: string[];
    attendeeCount: string;
    cta: string;
};

export const INITIAL_FEED_POSTS: FeedPostData[] = [
    {
        id: 'official-1',
        official: true,
        name: 'Maison Anversa',
        info: 'Officieel · 1 dag geleden · Antwerpen',
        badge: 'Officieel',
        badgeOfficial: true,
        content:
            'Productie update Heritage No.001 — De eerste batch frames zijn afgewerkt. De lederen grepen worden deze week aangebracht. De planning voor Q1 2027 ligt volledig op schema.\n\nWe houden u op de hoogte via de Heritage Letter en hier in de Community.',
        likes: 67,
        comments: 14,
    },
    {
        id: 'post-tj',
        initials: 'TJ',
        avatarBg: '#2A1810',
        name: 'Thomas Janssen',
        info: 'Nr. 007 · Padel Club Antwerpen · 2 uur geleden',
        badge: 'FC Lid',
        content:
            'Eerste sessie vandaag met Heritage No.001. Het verschil in gevoel met een standaard racket is onmiddellijk merkbaar. De lederen greep in het bijzonder — het warmt op in je hand en voelt na een uur aan alsof het altijd van jou is geweest.\n\nNummer 007. Trots lid van de Founding Circle.',
        imageLabel: 'Padel Court Session',
        likes: 12,
        comments: 3,
    },
    {
        id: 'post-av',
        initials: 'AV',
        avatarBg: '#1A2010',
        name: 'Amelie Verschueren',
        info: 'Nr. 023 · Padel One Brussels · 5 uur geleden',
        badge: 'FC Lid',
        content:
            'De Heritage doos is aangekomen. De unboxing is een ervaring op zich. De welkomstkaart, het paspoort, het certificaat — alles ademt kwaliteit. Het racket heb ik nog niet gespeeld maar ik ben al verliefd.\n\nDit is wat luxe aanvoelt.',
        likes: 28,
        comments: 7,
    },
    {
        id: 'post-mk',
        initials: 'MK',
        avatarBg: '#201520',
        name: 'Marc Kessels',
        info: 'Nr. 041 · Padel Club Rotterdam · Gisteren',
        badge: 'FC Lid',
        content:
            'Wie speelt er volgende zaterdag in Amsterdam? Ik zoek nog twee spelers voor een 4-set sessie. Niveau intermediair tot gevorderd. DM me of reageer hieronder.',
        likes: 5,
        comments: 8,
    },
];

export const SIDEBAR_MEMBERS = [
    { initials: 'TJ', name: 'Thomas J.', meta: 'Antwerpen', num: '007' },
    { initials: 'AV', name: 'Amelie V.', meta: 'Brussel', num: '023' },
    { initials: 'MK', name: 'Marc K.', meta: 'Rotterdam', num: '041' },
    { initials: 'LB', name: 'Lisa B.', meta: 'Amsterdam', num: '058' },
] as const;

export const COURT_PINS: CourtPin[] = [
    { id: 'antwerpen', top: '42%', left: '34%', label: 'Antwerpen ●' },
    { id: 'brussels', top: '58%', left: '38%', label: 'Brussel ●' },
    {
        id: 'amsterdam',
        top: '28%',
        left: '50%',
        label: 'Amsterdam',
        coming: true,
    },
    {
        id: 'rotterdam',
        top: '44%',
        left: '55%',
        label: 'Rotterdam',
        coming: true,
    },
    {
        id: 'hamburg',
        top: '18%',
        left: '60%',
        label: 'Hamburg',
        coming: true,
    },
];

export const COURT_CARDS: CourtCardData[] = [
    {
        id: 'antwerpen',
        name: 'Padel Club Antwerpen',
        status: 'Actief',
        location: 'Antwerpen, België · 2,4 km',
        stats: [
            { num: '8', label: 'Courts' },
            { num: '24', label: 'MA Leden' },
            { num: '4', label: 'Sessies/week' },
        ],
        tags: ['Founding Club Corner', 'Demo racket beschikbaar'],
    },
    {
        id: 'brussels',
        name: 'Padel One Brussels',
        status: 'Actief',
        location: 'Brussel, België · 48 km',
        stats: [
            { num: '6', label: 'Courts' },
            { num: '18', label: 'MA Leden' },
            { num: '2', label: 'Sessies/week' },
        ],
        tags: ['Club Corner', 'Heritage Paspoorten beschikbaar'],
    },
    {
        id: 'amsterdam',
        name: 'Amsterdam Padel Club',
        status: 'Q2 2027',
        statusComing: true,
        location: 'Amsterdam, Nederland · 128 km',
        stats: [
            { num: '10', label: 'Courts' },
            { num: '—', label: 'MA Leden' },
        ],
        tags: ['Binnenkort'],
        tagsMuted: true,
    },
    {
        id: 'rotterdam',
        name: 'Padel Rotterdam',
        status: 'Q2 2027',
        statusComing: true,
        location: 'Rotterdam, Nederland · 145 km',
        stats: [{ num: '8', label: 'Courts' }],
        tags: ['Binnenkort'],
        tagsMuted: true,
    },
];

export const SESSION_CARDS: SessionCardData[] = [
    {
        id: 'session-antwerp',
        title: 'Sessie in Antwerpen',
        spots: '1 plek vrij',
        meta: [
            '📅 Za 25 Jan 2027 · 10:00',
            '📍 Padel Club Antwerpen',
            '🎾 Intermediair',
        ],
        players: ['TJ', 'AV', 'MK'],
        emptySlots: 1,
    },
    {
        id: 'session-brussels',
        title: 'Sessie in Brussel',
        spots: '2 plekken vrij',
        meta: [
            '📅 Zo 26 Jan 2027 · 14:00',
            '📍 Padel One Brussels',
            '🎾 Alle niveaus',
        ],
        players: ['LB', 'KV'],
        emptySlots: 2,
    },
    {
        id: 'session-rotterdam',
        title: 'Sessie in Rotterdam',
        spots: '3 plekken vrij',
        meta: [
            '📅 Za 1 Feb 2027 · 09:00',
            '📍 Padel Rotterdam',
            '🎾 Gevorderd',
        ],
        players: ['PD'],
        emptySlots: 3,
    },
];

export const SESSION_LOCATIONS = [
    'Padel Club Antwerpen',
    'Padel One Brussels',
    'Amsterdam Padel Club',
    'Padel Rotterdam',
] as const;

export const SESSION_LEVELS = [
    'Alle niveaus',
    'Beginner',
    'Intermediair',
    'Gevorderd',
] as const;

export const SESSION_PLAYERS_WANTED = [
    '1 speler',
    '2 spelers',
    '3 spelers',
] as const;

export const EVENT_CARDS: EventCardData[] = [
    {
        id: 'event-launch',
        imageVariant: 1,
        imageLabel: 'MAISON ANVERSA',
        date: 'Za 14 Maart 2027 · 14:00 – 18:00',
        title: 'Heritage No.001 Launch Event',
        location: '📍 Padel Club Antwerpen · Antwerpen',
        attendees: ['TJ', 'AV', 'MK'],
        attendeeCount: '34 leden gaan',
        cta: 'Bevestig deelname →',
    },
    {
        id: 'event-morning',
        imageVariant: 2,
        imageLabel: 'PADEL SESSION',
        date: 'Za 22 Februari 2027 · 10:00 – 13:00',
        title: 'Founding Circle Padel Morning',
        location: '📍 Padel One Brussels · Brussel',
        attendees: ['LB', 'KV', 'PD'],
        attendeeCount: '12 leden gaan',
        cta: 'Bevestig deelname →',
    },
    {
        id: 'event-bts',
        imageVariant: 3,
        imageLabel: 'BEHIND THE SCENES',
        date: 'Za 8 Februari 2027 · 15:00 – 16:00',
        title: 'Heritage No.001 — Behind the Scenes',
        location: 'Online · Exclusief voor Founding Members',
        attendees: ['YS'],
        attendeeCount: 'Yusuf Savran presenteert · 78 gaan',
        cta: 'Registreer gratis →',
    },
];

export function tabFromIndex(index: number): CommunityTab {
    const tabs: CommunityTab[] = ['feed', 'courts', 'sessions', 'events'];

    return tabs[index] ?? 'feed';
}
