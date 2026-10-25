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
    requests_required?: boolean;
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
    pending_count?: number;
    viewer_status?: SessionParticipantStatus | null;
    is_pending?: boolean;
    can_withdraw?: boolean;
    registration_closed?: boolean;
    registration_closes_at?: string;
    lifecycle?: SessionLifecycle;
    lifecycle_label?: string;
    is_locked?: boolean;
    share_url?: string | null;
};

export type SessionParticipantStatus = 'pending' | 'confirmed' | 'rejected';

export type SessionLifecycle = 'scheduled' | 'held' | 'finished' | 'cancelled';

export type JoinRequest = SessionPlayer & {
    requested_at: string | null;
};

export type MatchType = 'singles' | 'doubles';

export type MatchFormat =
    | 'best_of_3'
    | 'super_tiebreak'
    | 'one_set'
    | 'short_sets';

export type MatchTypeOption = {
    value: MatchType;
    label: string;
    players_per_side: number;
};

export type MatchFormatOption = {
    value: MatchFormat;
    label: string;
    description: string;
    sets_to_win: number;
    max_sets: number;
    games_per_set: number;
    super_tiebreak: boolean;
};

export type ScoreSet = {
    a: number;
    b: number;
    tiebreak_a?: number | null;
    tiebreak_b?: number | null;
    is_super_tiebreak?: boolean;
};

export type ScoreSubmission = {
    id: number;
    status: 'pending' | 'approved' | 'rejected' | 'superseded';
    status_label: string;
    match_type: MatchType;
    match_type_label: string;
    format: MatchFormat;
    format_label: string;
    team_a: SessionPlayer[];
    team_b: SessionPlayer[];
    sets: ScoreSet[];
    sets_line: string;
    winner: 'a' | 'b' | null;
    submitted_by: string | null;
    submitted_at: string | null;
    reviewed_at: string | null;
};

export type SessionScoreboard = {
    lifecycle: SessionLifecycle;
    lifecycle_label: string;
    is_locked: boolean;
    is_finished: boolean;
    window_closes_at: string | null;
    window_open: boolean;
    official: ScoreSubmission | null;
    submissions: ScoreSubmission[];
    players: SessionPlayer[];
    sport: string;
    sport_label: string;
    match_types: MatchTypeOption[];
    formats: MatchFormatOption[];
    default_format: MatchFormat;
    min_players: number;
    can_record_match: boolean;
    can_submit: boolean;
    can_review: boolean;
    can_lock: boolean;
    can_unlock: boolean;
    can_finish: boolean;
};

export type MatchCardFormat = 'story' | 'post';

/** Public URLs for the shareable match card; the share token is the only key. */
export type MatchCardLinks = {
    share_url: string;
    story_url: string;
    post_url: string;
    download_story_url: string;
    download_post_url: string;
    has_result: boolean;
};

/** A player as printed on the card: initials, optional first name, Heritage number. */
export type MatchCardPlayer = {
    initials: string;
    first_name: string | null;
    edition: string | null;
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
