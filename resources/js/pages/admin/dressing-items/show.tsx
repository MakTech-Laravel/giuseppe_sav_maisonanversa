import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil, Shirt, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { DressingItemTranslationsDialog } from '@/components/admin/dressing-item-translations-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import dressingItems from '@/routes/admin/dressing-items';

interface DressingItemDetails {
    id: string;
    name: string;
    slug: string;
    category: string;
    description: string;
    image_url: string | null;
    status: 'coming_soon' | 'available';
    sort_order: number;
    is_published: boolean;
}

type LocaleCopy = {
    name: string;
    category: string;
    description: string;
};

type TranslationStatus = {
    name: boolean;
    category: boolean;
    description: boolean;
};

function Field({
    label,
    value,
    mono = false,
    pre = false,
}: {
    label: string;
    value: string;
    mono?: boolean;
    pre?: boolean;
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
                    pre && 'whitespace-pre-wrap',
                )}
            >
                {value}
            </p>
        </div>
    );
}

export default function ShowDressingItem({
    item,
    locales,
    translations,
    translationStatus,
}: {
    item: DressingItemDetails;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const statusLabel =
        item.status === 'available' ? t('Beschikbaar') : t('Binnenkort');

    return (
        <>
            <Head title={item.name} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={item.name}
                    description={t('Bekijk dit item zoals op de site.')}
                    icon={Shirt}
                >
                    <Button variant="outline" asChild>
                        <Link href={dressingItems.index(locale)}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={dressingItems.edit({
                                locale,
                                dressingItem: Number(item.id),
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
                                'Werk dit item bij of ga terug naar de lijst.',
                            )}
                        >
                            <div className="flex flex-col gap-2">
                                <Button asChild className="w-full">
                                    <Link
                                        href={dressingItems.edit({
                                            locale,
                                            dressingItem: Number(item.id),
                                        })}
                                    >
                                        <Pencil className="h-4 w-4" />{' '}
                                        {t('Bewerken')}
                                    </Link>
                                </Button>
                                <DressingItemTranslationsDialog
                                    dressingItemId={item.id}
                                    locales={locales}
                                    translations={translations}
                                    translationStatus={translationStatus}
                                />
                                <Button
                                    variant="outline"
                                    asChild
                                    className="w-full"
                                >
                                    <Link href={dressingItems.index(locale)}>
                                        <ArrowLeft className="h-4 w-4" />{' '}
                                        {t('Terug')}
                                    </Link>
                                </Button>
                                <ConfirmDeleteDialog
                                    description={t(
                                        'Dit item wordt permanent verwijderd.',
                                    )}
                                    onConfirm={() =>
                                        router.delete(
                                            dressingItems.destroy({
                                                locale,
                                                dressingItem: Number(item.id),
                                            }).url,
                                        )
                                    }
                                >
                                    <Button
                                        variant="destructive"
                                        className="w-full"
                                    >
                                        <Trash2 className="h-4 w-4" />{' '}
                                        {t('Verwijderen')}
                                    </Button>
                                </ConfirmDeleteDialog>
                            </div>
                        </AdminPanel>
                    }
                >
                    <AdminPanel
                        title={t('Inhoud')}
                        description={t(
                            'Zoals bezoekers dit item in de huidige taal zien.',
                        )}
                    >
                        <div className="mb-5 flex flex-wrap gap-2">
                            <Badge variant="secondary">{item.category}</Badge>
                            <Badge variant="secondary">{statusLabel}</Badge>
                            <Badge variant="secondary">
                                {item.is_published
                                    ? t('Gepubliceerd')
                                    : t('Concept')}
                            </Badge>
                        </div>

                        {item.image_url ? (
                            <img
                                src={item.image_url}
                                alt={item.name}
                                className="mb-5 aspect-4/3 w-full max-w-sm rounded-lg object-cover"
                            />
                        ) : (
                            <div className="mb-5 flex aspect-4/3 w-full max-w-sm items-center justify-center rounded-lg bg-muted text-xs text-muted-foreground">
                                {t('Geen afbeelding')}
                            </div>
                        )}

                        <div className="grid gap-5">
                            <Field
                                label={t('Beschrijving')}
                                value={item.description || t('—')}
                                pre
                            />
                        </div>
                    </AdminPanel>

                    <AdminPanel
                        title={t('Instellingen')}
                        description={t(
                            'Categorie, volgorde en publicatiestatus voor dit item.',
                        )}
                    >
                        <div className="grid items-start gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            <Field label={t('Slug')} value={item.slug} mono />
                            <Field
                                label={t('Volgorde')}
                                value={String(item.sort_order)}
                                mono
                            />
                            <Field
                                label={t('Status')}
                                value={
                                    item.is_published
                                        ? t('Gepubliceerd')
                                        : t('Concept')
                                }
                            />
                        </div>
                    </AdminPanel>
                </AdminResourceShell>
            </div>
        </>
    );
}

ShowDressingItem.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Kleedkamer',
            href: dressingItems.index(wayfinderLocale()),
        },
        { title: 'Gegevens', href: '#' },
    ],
};
