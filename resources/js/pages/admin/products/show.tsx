import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Package, Pencil } from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { ProductTranslationsDialog } from '@/components/admin/product-translations-dialog';
import type { ExistingFile } from '@/components/file-upload';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/hooks/use-locale';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import products from '@/routes/admin/products';
import type {
    ProductFaqFormData,
    ProductSectionCatalogueEntry,
    ProductSectionFormData,
} from '@/types/admin-product';

interface ProductDetails {
    id: number;
    name: string;
    slug: string;
    type: 'limited_edition' | 'simple';
    amount: string;
    currency: string;
    edition_total: number | null;
    edition_number_prefix: string;
    edition_number_postfix: string;
    archive_edition_numbers: number[];
    stock_quantity: number | null;
    is_published: boolean;
    grants_founding_circle: boolean;
    expected_delivery_label: string | null;
    eyebrow?: string | null;
    hero_eyebrow?: string | null;
    hero_subtitle?: string | null;
    description?: string | null;
    stripe_price_id: string | null;
    primary_image: ExistingFile | null;
    gallery_images: ExistingFile[];
    sections: ProductSectionFormData[];
    faqs: ProductFaqFormData[];
}

type LocaleCopy = {
    name: string;
    eyebrow: string;
    hero_eyebrow: string;
    hero_subtitle: string;
    description: string;
    expected_delivery_label: string;
};

type TranslationStatus = {
    name: boolean;
    eyebrow: boolean;
    hero_eyebrow: boolean;
    hero_subtitle: boolean;
    description: boolean;
    expected_delivery_label: boolean;
};

function Field({
    label,
    value,
    mono = false,
}: {
    label: string;
    value: string;
    mono?: boolean;
}) {
    return (
        <div className="grid min-w-0 gap-1">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p
                className={cn(
                    'text-sm font-medium wrap-break-word',
                    mono && 'font-mono tabular-nums',
                )}
            >
                {value}
            </p>
        </div>
    );
}

export default function ShowProduct({
    product,
    locales,
    translations,
    translationStatus,
    sectionCatalogue,
}: {
    product: ProductDetails;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
    sectionCatalogue: ProductSectionCatalogueEntry[];
}) {
    const { t } = useTranslation();
    const { locale: currentLocale } = useLocale();
    const locale = wayfinderLocale();
    const isLimited = product.type === 'limited_edition';
    const editionTotal = product.edition_total ?? 0;
    const padWidth = Math.max(3, String(Math.max(editionTotal, 1)).length);
    const archived = useMemo(
        () => new Set(product.archive_edition_numbers),
        [product.archive_edition_numbers],
    );

    const display = useMemo(() => {
        const localized = translations[currentLocale];

        return {
            name: localized?.name || product.name,
            eyebrow: localized?.eyebrow || product.eyebrow || '',
            hero_eyebrow: localized?.hero_eyebrow || product.hero_eyebrow || '',
            hero_subtitle:
                localized?.hero_subtitle || product.hero_subtitle || '',
            description: localized?.description || product.description || '',
            expected_delivery_label:
                localized?.expected_delivery_label ||
                product.expected_delivery_label ||
                '',
        };
    }, [currentLocale, translations, product]);

    const formatEditionLabel = (number: number): string =>
        `${product.edition_number_prefix}${String(number).padStart(padWidth, '0')}${product.edition_number_postfix}`;

    const exampleLabel =
        editionTotal > 0
            ? `${formatEditionLabel(1)} · ${formatEditionLabel(editionTotal)}`
            : null;

    return (
        <>
            <Head title={display.name} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={display.name}
                    description={t(
                        'Bekijk kerngegevens van dit catalogusproduct.',
                    )}
                    icon={Package}
                >
                    <Button variant="outline" asChild>
                        <Link href={products.index(locale)}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar product')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={products.edit({
                                locale,
                                product: product.id,
                            })}
                        >
                            <Pencil className="h-4 w-4" /> {t('Bewerken')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <AdminResourceShell
                    aside={
                        <AdminPanel
                            title={t('Acties')}
                            description={t(
                                'Werk dit catalogusproduct bij of ga terug naar de lijst.',
                            )}
                        >
                            <div className="flex flex-col gap-2">
                                <Button asChild className="w-full">
                                    <Link
                                        href={products.edit({
                                            locale,
                                            product: product.id,
                                        })}
                                    >
                                        <Pencil className="h-4 w-4" />{' '}
                                        {t('Bewerken')}
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    asChild
                                    className="w-full"
                                >
                                    <Link href={products.index(locale)}>
                                        <ArrowLeft className="h-4 w-4" />{' '}
                                        {t('Terug naar product')}
                                    </Link>
                                </Button>
                                <ProductTranslationsDialog
                                    productId={product.id}
                                    locales={locales}
                                    translations={translations}
                                    translationStatus={translationStatus}
                                />
                            </div>
                        </AdminPanel>
                    }
                >
                    <AdminPanel
                        title={t('Basisgegevens')}
                        description={t(
                            'Zoals bezoekers dit product in de huidige taal zien.',
                        )}
                    >
                        <div className="mb-5 flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                {isLimited
                                    ? t('Gelimiteerde editie')
                                    : t('Eenvoudige voorraad')}
                            </Badge>
                            <Badge variant="secondary">
                                {product.is_published
                                    ? t('Gepubliceerd')
                                    : t('Concept')}
                            </Badge>
                            {product.grants_founding_circle ? (
                                <Badge variant="secondary">
                                    {t('Founding Circle')}
                                </Badge>
                            ) : null}
                        </div>
                        <div className="grid items-start gap-5 md:grid-cols-2">
                            <Field label={t('Naam')} value={display.name} />
                            <Field
                                label={t('Slug')}
                                value={product.slug}
                                mono
                            />
                            <Field
                                label={t('Bedrag')}
                                value={`€ ${product.amount} ${product.currency.toUpperCase()}`}
                            />
                            <Field
                                label={t('Verwachte levering')}
                                value={
                                    display.expected_delivery_label || t('Geen')
                                }
                            />
                            <Field
                                label={t('Productlabel')}
                                value={display.eyebrow || t('Geen')}
                            />
                            <Field
                                label={t('Hero-eyebrow')}
                                value={display.hero_eyebrow || t('Geen')}
                            />
                            <div className="md:col-span-2">
                                <Field
                                    label={t('Hero-ondertitel')}
                                    value={display.hero_subtitle || t('Geen')}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <Field
                                    label={t('Productbeschrijving')}
                                    value={display.description || t('Geen')}
                                />
                            </div>
                            <Field
                                label={t('Stripe price ID')}
                                value={product.stripe_price_id ?? t('Geen')}
                                mono
                            />
                        </div>
                    </AdminPanel>

                    <AdminPanel
                        title={
                            isLimited ? t('Editie & voorraad') : t('Voorraad')
                        }
                        description={
                            isLimited
                                ? t(
                                      'Editiegrootte, nummerformaat en gearchiveerde nummers.',
                                  )
                                : t(
                                      'Eenvoudige voorraad van dit catalogusproduct.',
                                  )
                        }
                    >
                        {isLimited ? (
                            <div className="grid gap-5">
                                <div className="grid gap-5 md:grid-cols-3">
                                    <Field
                                        label={t('Editiegrootte')}
                                        value={
                                            product.edition_total != null
                                                ? String(product.edition_total)
                                                : t('Geen')
                                        }
                                    />
                                    <Field
                                        label={t('Prefix')}
                                        value={
                                            product.edition_number_prefix ||
                                            t('Geen')
                                        }
                                        mono
                                    />
                                    <Field
                                        label={t('Postfix')}
                                        value={
                                            product.edition_number_postfix ||
                                            t('Geen')
                                        }
                                        mono
                                    />
                                </div>

                                {exampleLabel ? (
                                    <p className="text-xs text-muted-foreground">
                                        {t('Voorbeeld')}:{' '}
                                        <span className="font-mono text-foreground">
                                            {exampleLabel}
                                        </span>
                                    </p>
                                ) : null}

                                {editionTotal > 0 ? (
                                    <div className="grid gap-3">
                                        <div className="flex flex-wrap items-end justify-between gap-2">
                                            <div>
                                                <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                                    {t('Archiefnummers')}
                                                </p>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {t(
                                                        'Gemarkeerde nummers zijn niet verkoopbaar.',
                                                    )}
                                                </p>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {t('{{count}} gearchiveerd', {
                                                    count: product
                                                        .archive_edition_numbers
                                                        .length,
                                                })}
                                            </p>
                                        </div>
                                        {/* contain-strict: tall grids must not inflate SidebarInset scroll */}
                                        <div className="h-96 overflow-hidden rounded-lg border bg-muted/20 contain-strict">
                                            <div className="h-full scrollbar-none overflow-y-auto p-3">
                                                <div className="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10">
                                                    {Array.from(
                                                        {
                                                            length: editionTotal,
                                                        },
                                                        (_, index) => index + 1,
                                                    ).map((number) => {
                                                        const isArchived =
                                                            archived.has(
                                                                number,
                                                            );

                                                        return (
                                                            <div
                                                                key={number}
                                                                className={cn(
                                                                    'flex flex-col items-center gap-1 rounded-md border px-1 py-2 text-center',
                                                                    isArchived
                                                                        ? 'border-gold/40 bg-gold/10'
                                                                        : 'border-transparent',
                                                                )}
                                                            >
                                                                <span
                                                                    className={cn(
                                                                        'size-2 rounded-full',
                                                                        isArchived
                                                                            ? 'bg-gold'
                                                                            : 'bg-muted-foreground/30',
                                                                    )}
                                                                    aria-hidden
                                                                />
                                                                <span className="max-w-full truncate font-mono text-[10px] tabular-nums">
                                                                    {formatEditionLabel(
                                                                        number,
                                                                    )}
                                                                </span>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ) : null}
                            </div>
                        ) : (
                            <Field
                                label={t('Voorraad')}
                                value={
                                    product.stock_quantity != null
                                        ? String(product.stock_quantity)
                                        : t('Geen')
                                }
                            />
                        )}
                    </AdminPanel>

                    {(product.primary_image ||
                        product.gallery_images.length > 0) && (
                        <AdminPanel
                            title={t('Afbeeldingen')}
                            description={t(
                                'Primaire coverfoto en galerijbeelden.',
                            )}
                        >
                            <div className="grid gap-8">
                                {product.primary_image ? (
                                    <div className="grid gap-2">
                                        <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                            {t('Primaire afbeelding')}
                                        </p>
                                        <div className="w-full max-w-sm overflow-hidden rounded-lg border bg-card">
                                            <div className="flex aspect-4/3 max-h-64 w-full items-center justify-center bg-muted">
                                                <img
                                                    src={
                                                        product.primary_image
                                                            .url
                                                    }
                                                    alt={display.name}
                                                    className="max-h-full max-w-full object-contain"
                                                />
                                            </div>
                                            <p className="truncate px-3 py-2 text-xs text-muted-foreground">
                                                {product.primary_image.name}
                                            </p>
                                        </div>
                                    </div>
                                ) : null}

                                {product.gallery_images.length > 0 ? (
                                    <div className="grid gap-2">
                                        <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                            {t('Galerij')}
                                        </p>
                                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                            {product.gallery_images.map(
                                                (image) => (
                                                    <div
                                                        key={image.id}
                                                        className="overflow-hidden rounded-lg border bg-card"
                                                    >
                                                        <div className="aspect-video max-h-52 w-full overflow-hidden bg-muted">
                                                            <img
                                                                src={image.url}
                                                                alt={
                                                                    image.name ??
                                                                    display.name
                                                                }
                                                                className="h-full w-full object-cover"
                                                            />
                                                        </div>
                                                        <p className="truncate px-2.5 py-2 text-[11px] text-muted-foreground">
                                                            {image.name}
                                                        </p>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                ) : null}
                            </div>
                        </AdminPanel>
                    )}

                    <AdminPanel
                        title={t('Paginasecties')}
                        description={t(
                            'Overzicht van de secties op de publieke productpagina. Bewerk de inhoud via het tabblad Secties.',
                        )}
                    >
                        {product.sections.length > 0 ? (
                            <div className="grid gap-3">
                                {product.sections.map((section) => {
                                    const entry = sectionCatalogue.find(
                                        (candidate) =>
                                            candidate.key === section.key,
                                    );

                                    return (
                                        <div
                                            key={section.key}
                                            className="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-muted/20 px-4 py-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">
                                                    {entry?.label ??
                                                        section.key}
                                                </p>
                                                {entry?.uses_items ? (
                                                    <p className="text-xs text-muted-foreground">
                                                        {t(
                                                            '{{count}} onderdelen',
                                                            {
                                                                count: section
                                                                    .items
                                                                    .length,
                                                            },
                                                        )}
                                                    </p>
                                                ) : null}
                                            </div>
                                            <Badge
                                                variant={
                                                    section.is_visible
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                            >
                                                {section.is_visible
                                                    ? t('Zichtbaar')
                                                    : t('Verborgen')}
                                            </Badge>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                {t('Nog geen secties voor dit product.')}
                            </p>
                        )}
                    </AdminPanel>

                    <AdminPanel
                        title={t('Veelgestelde vragen')}
                        description={t(
                            'Vragen en antwoorden die alleen op dit product worden getoond.',
                        )}
                    >
                        {product.faqs.length > 0 ? (
                            <div className="grid gap-4">
                                {product.faqs.map((faq) => (
                                    <div
                                        key={faq.uid}
                                        className="rounded-lg border bg-muted/20 p-4"
                                    >
                                        <div className="mb-1.5 flex items-start justify-between gap-3">
                                            <p className="text-sm font-medium">
                                                {faq.question}
                                            </p>
                                            <Badge
                                                variant={
                                                    faq.is_published
                                                        ? 'secondary'
                                                        : 'outline'
                                                }
                                                className="shrink-0"
                                            >
                                                {faq.is_published
                                                    ? t('Gepubliceerd')
                                                    : t('Concept')}
                                            </Badge>
                                        </div>
                                        <p className="text-sm break-words text-muted-foreground">
                                            {faq.answer}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                {t('Nog geen vragen voor dit product.')}
                            </p>
                        )}
                    </AdminPanel>

                    <AdminPanel
                        title={t('Publicatie')}
                        description={t(
                            'Publicatiestatus en Founding Circle-toegang.',
                        )}
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                label={t('Status')}
                                value={
                                    product.is_published
                                        ? t('Gepubliceerd')
                                        : t('Concept')
                                }
                            />
                            <Field
                                label={t('Founding Circle')}
                                value={
                                    product.grants_founding_circle
                                        ? t('Geeft toegang')
                                        : t('Geen toegang')
                                }
                            />
                        </div>
                    </AdminPanel>
                </AdminResourceShell>
            </div>
        </>
    );
}

ShowProduct.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Product', href: products.index(wayfinderLocale()) },
        { title: 'Bekijken', href: products.index(wayfinderLocale()) },
    ],
};
