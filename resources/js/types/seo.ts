export type SeoHreflangLink = {
    hreflang: string;
    href: string;
};

export type SeoDocument = {
    title: string;
    description: string;
    keywords: string | null;
    canonical: string;
    robots: string | null;
    ogType: string;
    ogImage: string;
    ogImageWidth: number | null;
    ogImageHeight: number | null;
    siteName: string;
    locale: string;
    localeAlternates: string[];
    hreflang: SeoHreflangLink[];
    jsonLd: Record<string, unknown>[];
    articlePublishedTime: string | null;
    articleModifiedTime: string | null;
};
