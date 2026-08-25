export type ProductCard = {
    id: number;
    slug: string;
    name: string;
    status: string;
    hero_subtitle: string;
    cover_asset: string | null;
    amount: string;
    display_amount: string;
    currency: string;
    type: string;
};

export type ProductPaginator = {
    data: ProductCard[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
};
