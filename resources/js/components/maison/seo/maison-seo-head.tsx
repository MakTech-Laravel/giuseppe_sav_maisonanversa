import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useLocale } from '@/hooks/use-locale';
import { imageAsset  } from '@/lib/imagery';
import type {ImageAssetName} from '@/lib/imagery';
import { maisonUrl  } from '@/lib/maison-navigation';
import type {MaisonPage} from '@/lib/maison-navigation';
import type { Locale } from '@/types/locale';

type SharedProps = {
    appUrl: string;
    seoImage: string;
};

const OG_LOCALE: Record<Locale, string> = {
    nl: 'nl_BE',
    en: 'en_GB',
    fr: 'fr_BE',
};

function pageHref(
    origin: string,
    page: MaisonPage,
    locale: Locale,
    articleSlug?: string,
): string {
    const base = `${origin}${maisonUrl(page, locale)}`;

    return articleSlug ? `${base}/${articleSlug}` : base;
}

function useMaisonSeo(page: MaisonPage): { title: string; description: string } {
    const { t } = useTranslation();

    switch (page) {
        case 'home':
            return {
                title: t(
                    'Maison Anversa — European Heritage Sports and Lifestyle House',
                ),
                description: t(
                    'Een Europees erfgoedhuis, geworteld in Antwerpen. Heritage No.001 — beperkt tot 100 stuks. Elk genummerd. De Founding Edition wordt nooit herhaald.',
                ),
            };
        case 'house':
            return {
                title: t('Het Huis — Maison Anversa'),
                description: t(
                    'Ontdek het Huis van Maison Anversa — negen kamers, één erfgoedverhaal, geworteld in Antwerpen.',
                ),
            };
        case 'product':
            return {
                title: t('Heritage No.001 — Maison Anversa'),
                description: t(
                    'Heritage No.001 — het eerste hoofdstuk van Maison Anversa. Beperkt tot 100 genummerde stuks wereldwijd.',
                ),
            };
        case 'story':
            return {
                title: t('Ons Verhaal — Maison Anversa'),
                description: t(
                    'Het verhaal van Maison Anversa — van Antwerpen naar een Europees erfgoedhuis voor sport en lifestyle.',
                ),
            };
        case 'circle':
            return {
                title: t('Founding Circle — Maison Anversa'),
                description: t(
                    'Word lid van de Founding Circle — exclusieve toegang, events en het erfgoed van Maison Anversa.',
                ),
            };
        case 'dressing':
            return {
                title: t('Kleedkamer — Maison Anversa'),
                description: t(
                    'De Kleedkamer van Maison Anversa — curated sportswear en lifestyle, met dezelfde zorg als ons erfgoed.',
                ),
            };
        case 'journal':
            return {
                title: t('Journal — Maison Anversa'),
                description: t(
                    'Het Journal van Maison Anversa — verhalen over ambacht, Antwerpen en het erfgoed van sport.',
                ),
            };
        case 'community':
            return {
                title: t('Community — Maison Anversa'),
                description: t(
                    'De Community van Maison Anversa — sessions, events en een netwerk van gelijkgestemde leden.',
                ),
            };
        case 'corner':
            return {
                title: t('Club Corner — Maison Anversa'),
                description: t(
                    'Club Corner — exclusieve voordelen en early access voor leden van Maison Anversa.',
                ),
            };
        case 'contact':
            return {
                title: t('Contact — Maison Anversa'),
                description: t(
                    'Neem contact op met Maison Anversa — vragen over Heritage No.001, bestellingen of pers.',
                ),
            };
        case 'privacy':
            return {
                title: t('Privacybeleid — Maison Anversa'),
                description: t(
                    'Privacybeleid van Maison Anversa — hoe wij uw gegevens verwerken en beschermen.',
                ),
            };
        case 'terms':
            return {
                title: t('Algemene voorwaarden — Maison Anversa'),
                description: t(
                    'Algemene voorwaarden van Maison Anversa — bestellingen, levering en garantie.',
                ),
            };
        case 'shipping':
            return {
                title: t('Verzending & Retour — Maison Anversa'),
                description: t(
                    'Verzending en retour bij Maison Anversa — leveringstijden, kosten en retourbeleid.',
                ),
            };
        case 'care':
            return {
                title: t('Zorg & Garantie — Maison Anversa'),
                description: t(
                    'Zorg en garantie voor Heritage No.001 — onderhoud, reparatie en klantenservice van Maison Anversa.',
                ),
            };
    }
}

type MaisonSeoHeadProps = {
    page: MaisonPage;
    /** Overrides the page title, used by Journal pieces. */
    title?: string;
    /** Overrides the meta description. */
    description?: string;
    /** Appended to the journal path so article URLs stay canonical. */
    articleSlug?: string;
    /** Social image for a Journal piece; falls back to the house default. */
    image?: ImageAssetName;
    noIndex?: boolean;
};

/**
 * Canonical URL, hreflang alternates, and social cards for a public page.
 *
 * Titles bypass the global `title - AppName` suffix so search and social
 * previews receive the full Maison Anversa copy.
 */
export function MaisonSeoHead({
    page,
    title: titleOverride,
    description: descriptionOverride,
    articleSlug,
    image: imageOverride,
    noIndex = false,
}: MaisonSeoHeadProps) {
    const { locale, availableLocales } = useLocale();
    const { appUrl, seoImage } = usePage<SharedProps>().props;
    const defaults = useMaisonSeo(page);
    const title = titleOverride ?? defaults.title;
    const description = descriptionOverride ?? defaults.description;

    const origin = appUrl.replace(/\/+$/, '');
    const canonical = pageHref(origin, page, locale, articleSlug);
    const image = imageOverride
        ? `${origin}/${imageAsset(imageOverride).path}`
        : `${origin}${seoImage}`;

    return (
        <Head title={title} titleTemplate="%s">
            {noIndex && (
                <meta head-key="robots" name="robots" content="noindex, nofollow" />
            )}
            <meta head-key="description" name="description" content={description} />
            <link head-key="canonical" rel="canonical" href={canonical} />
            {availableLocales.map((alternateLocale) => (
                <link
                    key={alternateLocale}
                    head-key={`hreflang-${alternateLocale}`}
                    rel="alternate"
                    hrefLang={alternateLocale}
                    href={pageHref(origin, page, alternateLocale, articleSlug)}
                />
            ))}
            <link
                head-key="hreflang-x-default"
                rel="alternate"
                hrefLang="x-default"
                href={pageHref(origin, page, 'nl', articleSlug)}
            />
            <meta
                head-key="og:type"
                property="og:type"
                content={articleSlug ? 'article' : 'website'}
            />
            <meta head-key="og:url" property="og:url" content={canonical} />
            <meta head-key="og:title" property="og:title" content={title} />
            <meta
                head-key="og:description"
                property="og:description"
                content={description}
            />
            <meta head-key="og:image" property="og:image" content={image} />
            <meta
                head-key="og:locale"
                property="og:locale"
                content={OG_LOCALE[locale]}
            />
            <meta
                head-key="og:site_name"
                property="og:site_name"
                content="Maison Anversa"
            />
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
        </Head>
    );
}
