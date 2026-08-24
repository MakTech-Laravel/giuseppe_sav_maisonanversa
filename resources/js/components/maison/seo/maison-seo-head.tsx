import { Head, usePage } from '@inertiajs/react';
import { imageAsset } from '@/lib/imagery';
import type { ImageAssetName } from '@/lib/imagery';
import type { SeoDocument } from '@/types/seo';

type MaisonSeoHeadProps = {
    /** Overrides the shared document title. */
    title?: string;
    /** Overrides the shared meta description. */
    description?: string;
    /** Social image for a Journal piece; falls back to the house default. */
    image?: ImageAssetName;
    noIndex?: boolean;
};

/**
 * Canonical URL, hreflang alternates, JSON-LD, and social cards.
 *
 * Copy comes from the shared `seo` document so the Blade fallback and this
 * head cannot disagree. Titles bypass the global `title - AppName` suffix.
 */
export function MaisonSeoHead({
    title: titleOverride,
    description: descriptionOverride,
    image: imageOverride,
    noIndex = false,
}: MaisonSeoHeadProps) {
    const { appUrl, seo } = usePage<{
        appUrl?: string;
        seo?: SeoDocument;
    }>().props;

    if (!appUrl || !seo) {
        return null;
    }

    const origin = appUrl.replace(/\/+$/, '');
    const title = titleOverride ?? seo.title;
    const description = descriptionOverride ?? seo.description;
    const robots = noIndex ? 'noindex, nofollow' : seo.robots;
    const image = imageOverride
        ? `${origin}/${imageAsset(imageOverride).path}`
        : seo.ogImage;

    return (
        <Head title={title} titleTemplate="%s">
            {robots && (
                <meta head-key="robots" name="robots" content={robots} />
            )}
            <meta
                head-key="description"
                name="description"
                content={description}
            />
            <link head-key="canonical" rel="canonical" href={seo.canonical} />
            {seo.hreflang.map((alternate) => (
                <link
                    key={alternate.hreflang}
                    head-key={`hreflang-${alternate.hreflang}`}
                    rel="alternate"
                    hrefLang={alternate.hreflang}
                    href={alternate.href}
                />
            ))}
            <meta
                head-key="og:type"
                property="og:type"
                content={seo.ogType}
            />
            <meta head-key="og:url" property="og:url" content={seo.canonical} />
            <meta head-key="og:title" property="og:title" content={title} />
            <meta
                head-key="og:description"
                property="og:description"
                content={description}
            />
            <meta head-key="og:image" property="og:image" content={image} />
            {seo.ogImageWidth && (
                <meta
                    head-key="og:image:width"
                    property="og:image:width"
                    content={String(seo.ogImageWidth)}
                />
            )}
            {seo.ogImageHeight && (
                <meta
                    head-key="og:image:height"
                    property="og:image:height"
                    content={String(seo.ogImageHeight)}
                />
            )}
            <meta
                head-key="og:locale"
                property="og:locale"
                content={seo.locale}
            />
            {seo.localeAlternates.map((alternateLocale) => (
                <meta
                    key={alternateLocale}
                    head-key={`og:locale:alternate:${alternateLocale}`}
                    property="og:locale:alternate"
                    content={alternateLocale}
                />
            ))}
            <meta
                head-key="og:site_name"
                property="og:site_name"
                content={seo.siteName}
            />
            {seo.articlePublishedTime && (
                <meta
                    head-key="article:published_time"
                    property="article:published_time"
                    content={seo.articlePublishedTime}
                />
            )}
            {seo.articleModifiedTime && (
                <meta
                    head-key="article:modified_time"
                    property="article:modified_time"
                    content={seo.articleModifiedTime}
                />
            )}
            <meta
                head-key="twitter:card"
                name="twitter:card"
                content="summary_large_image"
            />
            <meta head-key="twitter:title" name="twitter:title" content={title} />
            <meta
                head-key="twitter:description"
                name="twitter:description"
                content={description}
            />
            <meta head-key="twitter:image" name="twitter:image" content={image} />
            {seo.jsonLd.map((graph, index) => (
                <script
                    key={index}
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: JSON.stringify(graph),
                    }}
                />
            ))}
        </Head>
    );
}
