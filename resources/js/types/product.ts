export const PRODUCT_SECTION_KEYS = [
    'specs',
    'includes',
    'guarantees',
    'unboxing',
    'craft',
    'trust',
    'service',
    'faq',
    'related',
] as const;

export type ProductSectionKey = (typeof PRODUCT_SECTION_KEYS)[number];

export type ProductSectionItem = {
    id: number;
    number_label: string;
    icon: string;
    title: string;
    body: string;
    image: string | null;
};

export type ProductSection = {
    key: ProductSectionKey;
    eyebrow: string;
    heading: string;
    subheading: string;
    intro: string;
    image: string | null;
    /** Related section only: appends the editorial "Het Huis" card. */
    include_house_card: boolean;
    sort_order: number;
    items: ProductSectionItem[];
};

export type ProductFaqItem = {
    id: number;
    question: string;
    answer: string;
};

export type ProductPageData = {
    id: number;
    slug: string;
    name: string;
    eyebrow: string;
    hero_eyebrow: string;
    hero_subtitle: string;
    description: string;
    status: string;
    gallery: string[];
    sections: ProductSection[];
    faqs: ProductFaqItem[];
    amount: string;
    currency: string;
    expected_delivery_label: string;
    edition_total: number | null;
};

/** Looks up a section by key; hidden sections are never sent by the server. */
export function findProductSection(
    sections: ProductSection[],
    key: ProductSectionKey,
): ProductSection | undefined {
    return sections.find((section) => section.key === key);
}

export function productSectionItems(
    sections: ProductSection[],
    key: ProductSectionKey,
): ProductSectionItem[] {
    return findProductSection(sections, key)?.items ?? [];
}

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
