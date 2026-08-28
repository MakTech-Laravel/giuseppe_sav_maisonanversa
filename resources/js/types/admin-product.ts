export type ProductSectionCatalogueEntry = {
    key: string;
    label: string;
    description: string;
    uses_heading: boolean;
    uses_image: boolean;
    uses_items: boolean;
    item_fields: string[];
    sort_order: number;
};

export type ProductSectionItemFormData = {
    uid: string;
    number_label: string;
    icon: string;
    title: string;
    body: string;
};

export type ProductSectionFormData = {
    id?: number;
    key: string;
    eyebrow: string;
    heading: string;
    subheading: string;
    intro: string;
    image_key: string;
    image: File | null;
    remove_image: boolean;
    existing_image: string | null;
    is_visible: boolean;
    include_house_card: boolean;
    sort_order: number;
    items: ProductSectionItemFormData[];
};

export type ProductFaqFormData = {
    uid: string;
    id?: number;
    question: string;
    answer: string;
    is_published: boolean;
};

let uidCounter = 0;

/** Stable React key for repeater rows that have no server id yet. */
export function nextUid(prefix: string): string {
    uidCounter += 1;

    return `${prefix}-${uidCounter}`;
}

export function emptySectionItem(): ProductSectionItemFormData {
    return {
        uid: nextUid('item'),
        number_label: '',
        icon: '',
        title: '',
        body: '',
    };
}

export function emptyFaq(): ProductFaqFormData {
    return {
        uid: nextUid('faq'),
        question: '',
        answer: '',
        is_published: true,
    };
}

/**
 * Builds one form row per catalogue entry, merging in whatever the server
 * already stored so newly added section keys appear automatically.
 */
export function buildSectionForm(
    catalogue: ProductSectionCatalogueEntry[],
    stored: Partial<ProductSectionFormData>[] = [],
): ProductSectionFormData[] {
    return catalogue
        .map((entry) => {
            const existing = stored.find((section) => section.key === entry.key);

            return {
                key: entry.key,
                eyebrow: existing?.eyebrow ?? '',
                heading: existing?.heading ?? '',
                subheading: existing?.subheading ?? '',
                intro: existing?.intro ?? '',
                image_key: existing?.image_key ?? '',
                image: null,
                remove_image: false,
                existing_image: existing?.existing_image ?? null,
                is_visible: existing?.is_visible ?? true,
                include_house_card: existing?.include_house_card ?? true,
                sort_order: existing?.sort_order ?? entry.sort_order,
                items: (existing?.items ?? []).map((item) => ({
                    uid: item.uid ?? nextUid('item'),
                    number_label: item.number_label ?? '',
                    icon: item.icon ?? '',
                    title: item.title ?? '',
                    body: item.body ?? '',
                })),
            } satisfies ProductSectionFormData;
        })
        .sort((a, b) => a.sort_order - b.sort_order);
}
