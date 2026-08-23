import type { CommunityTab } from '@/components/maison/community/community-tabs';

export type FeedComment = {
    id: string;
    name: string;
    initials: string;
    body: string;
    info: string;
};

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
    comments: FeedComment[];
    liked?: boolean;
};

export type SessionCardData = {
    id: string;
    title: string;
    spots: string;
    meta: string[];
    players: string[];
    emptySlots: number;
    joined?: boolean;
};

export type CommunitySessionPayload = {
    id: string;
    location: string;
    starts_at: string;
    capacity: number | null;
    level: string | null;
    notes: string | null;
    host: string;
    joined: boolean;
    spots: number | null;
    players: string[];
};

export type CommunityEventPayload = {
    id: string;
    title: string;
    description: string | null;
    starts_at: string;
    location: string;
    capacity: number | null;
    thumbnail_url: string | null;
    joined: boolean;
    is_full: boolean;
    rsvp_count: number;
    attendees: string[];
};

export type CommunityCourtPayload = {
    id: string;
    title: string;
    body: string | null;
    location: string | null;
    lat: number | null;
    lng: number | null;
    pin_top: string | null;
    pin_left: string | null;
    coming: boolean;
};

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

export function tabFromIndex(index: number): CommunityTab {
    const tabs: CommunityTab[] = ['feed', 'courts', 'sessions', 'events'];

    return tabs[index] ?? 'feed';
}
