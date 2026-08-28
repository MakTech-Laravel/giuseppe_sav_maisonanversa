export type InquiryKindOption = {
    value: string;
    label: string;
};

export type InquiryListItem = {
    id: string;
    type: string;
    type_label: string;
    name: string;
    email: string;
    subject: string | null;
    message: string;
    seen: boolean;
    locale: string;
    created_at: string | null;
    user_id: number | null;
};

export type InquiryDetails = InquiryListItem & {
    phone: string | null;
    meta: Record<string, unknown> | null;
    ip: string | null;
};

export type InquiryFilters = {
    search: string;
    status: string;
    kind: string;
    per_page: number;
};

export type InquiryRouteHelpers = {
    index: (
        locale: string,
        options?: { query?: Record<string, string | number | undefined> },
    ) => { url: string };
    show: (args: { locale: string; inquiry: number }) => { url: string };
    seen: (args: { locale: string; inquiry: number }) => { url: string };
    destroy: (args: { locale: string; inquiry: number }) => { url: string };
};
