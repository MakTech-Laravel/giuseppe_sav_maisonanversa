import type { ClubCard } from '@/types/session';

export type SessionLifecycle = 'active' | 'ended' | 'cancelled';

export type AdminSessionRow = {
    id: string;
    host: string;
    sport: string;
    sport_label: string;
    club_id: number | null;
    club_name: string | null;
    club_city: string | null;
    starts_at: string;
    capacity: number;
    participants_count: number;
    level_label: string;
    lifecycle: SessionLifecycle;
};

export type AdminSessionPlayer = {
    id: number;
    name: string;
    email: string;
    is_host: boolean;
    joined_at: string | null;
};

export type AdminSessionDetail = AdminSessionRow & {
    court_status_label: string;
    gender_label: string;
    duration_minutes: number;
    ends_at: string | null;
    notes: string | null;
    club: ClubCard | null;
    host_id: number;
    players: AdminSessionPlayer[];
};
