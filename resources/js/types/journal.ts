import type { ImageAssetName } from '@/lib/imagery';

export type JournalCard = {
    slug: string;
    asset: ImageAssetName;
    category: string;
    title: string;
    excerpt: string;
    author: string;
    date: string;
    meta: string;
};

export type JournalArticle = JournalCard & {
    body: string[];
};

export type JournalPaginator = {
    data: JournalCard[];
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
