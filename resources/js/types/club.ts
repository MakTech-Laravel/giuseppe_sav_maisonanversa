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
    is_session_venue: boolean;
    show_on_corner_page: boolean;
    has_corner: boolean;
    corner_published: boolean;
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
    corner_pipeline_status: string | null;
    corner_pipeline_label: string | null;
    corner_title: string | null;
    corner_body: string | null;
    corner_location: string | null;
    sort_order: number;
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
    is_session_venue: boolean;
    show_on_corner_page: boolean;
    corner_pipeline_status: string | null;
    has_corner: boolean;
    corner_published: boolean;
    corner_title: string | null;
    corner_body: string | null;
    corner_location: string | null;
    sort_order: number;
    image_url: string | null;
};

export type ClubFormOptions = {
    sports: ClubStatusOption[];
    statuses: ClubStatusOption[];
    pipelineStatuses: ClubStatusOption[];
};

export type MergeCandidate = {
    id: number;
    name: string;
    street: string | null;
    postal_code: string | null;
    city: string | null;
    country: string;
    lat: number | null;
    lng: number | null;
    website: string | null;
    phone: string | null;
    status: string;
    status_label: string;
    sports: string[];
    is_partner: boolean;
    is_session_venue: boolean;
    show_on_corner_page: boolean;
    corner_pipeline_status: string | null;
    corner_pipeline_label: string | null;
    has_corner: boolean;
    corner_published: boolean;
    corner_title: string | null;
    corner_body: string | null;
    corner_location: string | null;
    sort_order: number;
    image_url: string | null;
};

export type AdminClubSessionRow = {
    id: string;
    host: string;
    sport_label: string;
    starts_at: string;
    participants_count: number;
    capacity: number;
};

export type ClubCornerLocaleCopy = {
    corner_title: string;
    corner_body: string;
    corner_location: string;
};

export type ClubCornerTranslationStatus = {
    corner_title: boolean;
    corner_body: boolean;
    corner_location: boolean;
};
