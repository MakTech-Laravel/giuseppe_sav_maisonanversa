export type ClubStatusOption = {
    value: string;
    label: string;
};

export type AdminClubRow = {
    id: number;
    name: string;
    city: string;
    address: string;
    sports: string[];
    status: string;
    status_label: string;
    is_partner: boolean;
    sessions_count: number;
    created_at: string | null;
};

export type AdminClubDetail = AdminClubRow & {
    street: string | null;
    postal_code: string | null;
    country: string;
    website: string | null;
    phone: string | null;
    lat: number | null;
    lng: number | null;
    image_url: string | null;
    submitted_by: { id: number; name: string; email: string } | null;
    approved_by: { id: number; name: string } | null;
    approved_at: string | null;
    merged_into: { id: number; name: string; city: string } | null;
    duplicates: { id: number; name: string; city: string }[];
};

export type AdminClubFormValues = {
    id: number;
    name: string;
    sports: string[];
    street: string | null;
    postal_code: string | null;
    city: string;
    country: string;
    lat: number | null;
    lng: number | null;
    website: string | null;
    phone: string | null;
    status: string;
    is_partner: boolean;
    image_url: string | null;
};

export type ClubFormOptions = {
    sports: ClubStatusOption[];
    statuses: ClubStatusOption[];
};

export type MergeCandidate = {
    id: number;
    name: string;
    city: string;
};

export type AdminClubSessionRow = {
    id: string;
    host: string;
    sport_label: string;
    starts_at: string;
    participants_count: number;
    capacity: number;
};
