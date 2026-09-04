export type SessionTab = 'open' | 'mine' | 'past';

export const SESSION_TABS: SessionTab[] = ['open', 'mine', 'past'];

export type SessionPlayer = {
    id: number;
    name: string;
    initials: string;
    avatar_url: string | null;
    is_host: boolean;
};

export type ClubCard = {
    id: number;
    name: string;
    city: string | null;
    address: string;
    is_partner: boolean;
    image_url: string | null;
    sports: string[];
};

export type SessionCard = {
    id: string;
    sport: string;
    sport_label: string;
    club: ClubCard | null;
    starts_at: string;
    ends_at: string | null;
    duration_minutes: number;
    court_status: string;
    court_status_label: string;
    level: string;
    level_label: string;
    gender: string;
    gender_label: string;
    capacity: number;
    participants_count: number;
    open_slots: number;
    players: SessionPlayer[];
    notes: string | null;
    host: SessionPlayer;
    is_full: boolean;
    is_past: boolean;
    is_cancelled: boolean;
    is_host: boolean;
    is_joined: boolean;
    can_join: boolean;
    can_leave: boolean;
    can_manage: boolean;
};

export type SessionOption = {
    value: string;
    label: string;
};

export type DurationOption = {
    value: number;
    label: string;
};

export type SessionFormOptions = {
    sports: SessionOption[];
    levels: SessionOption[];
    genders: SessionOption[];
    court_statuses: SessionOption[];
    durations: DurationOption[];
    capacities: number[];
    partner_clubs: ClubCard[];
};

export type SessionTabCounts = Record<SessionTab, number>;

export type EventCard = {
    id: string;
    title: string;
    description: string | null;
    location: string;
    starts_at: string;
    capacity: number | null;
    status: string;
    thumbnail_url: string | null;
    rsvp_count: number;
    attendees: SessionPlayer[];
    is_full: boolean;
    is_past: boolean;
    is_joined: boolean;
    can_join: boolean;
    can_leave: boolean;
};
