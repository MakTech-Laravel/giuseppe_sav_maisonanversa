import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MapPin, Pencil, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { CourtTranslationsDialog } from '@/components/admin/court-translations-dialog';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import courtsRoutes from '@/routes/admin/courts';

interface CourtDetail {
    id: string;
    title: string;
    body?: string | null;
    location: string;
    lat: number | null;
    lng: number | null;
    sort_order: number;
    is_published: boolean;
}

type LocaleCopy = {
    title: string;
    body: string;
    location: string;
};

type TranslationStatus = {
    title: boolean;
    body: boolean;
    location: boolean;
};

interface CourtShowProps {
    court: CourtDetail;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}

function Field({
    label,
    value,
    pre = false,
}: {
    label: string;
    value: string;
    pre?: boolean;
}) {
    return (
        <div className="grid min-w-0 gap-1">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p
                className={cn(
                    'wrap-break-word text-sm font-medium',
                    pre && 'whitespace-pre-wrap',
                )}
            >
                {value}
            </p>
        </div>
    );
}

export default function CourtShow({
    court,
    locales,
    translations,
    translationStatus,
}: CourtShowProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const localized = translations[locale];

    const title = localized?.title || court.title;
    const body = localized?.body || court.body || '';
    const location = localized?.location || court.location;

    return (
        <>
            <Head title={title} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={title}
                    description={t(
                        'Bekijk deze Club Corner zoals op de communitykaart.',
                    )}
                    icon={MapPin}
                >
                    <Button variant="outline" asChild>
                        <Link href={courtsRoutes.index(locale)}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={courtsRoutes.edit({
                                locale,
                                court: Number(court.id),
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
                                'Werk deze Club Corner bij of beheer vertalingen.',
                            )}
                        >
                            <div className="flex flex-col gap-2">
                                <CourtTranslationsDialog
                                    courtId={court.id}
                                    locales={locales}
                                    translations={translations}
                                    translationStatus={translationStatus}
                                />
                                <Button asChild className="w-full">
                                    <Link
                                        href={courtsRoutes.edit({
                                            locale,
                                            court: Number(court.id),
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
                                    <Link href={courtsRoutes.index(locale)}>
                                        <ArrowLeft className="h-4 w-4" />{' '}
                                        {t('Terug')}
                                    </Link>
                                </Button>
                                <ConfirmDeleteDialog
                                    description={t(
                                        'Deze Club Corner wordt permanent verwijderd.',
                                    )}
                                    onConfirm={() =>
                                        router.delete(
                                            courtsRoutes.destroy({
                                                locale,
                                                court: Number(court.id),
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
                            'Zoals leden deze Club Corner in de huidige taal zien.',
                        )}
                    >
                        <div className="mb-5 flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                {court.is_published
                                    ? t('Gepubliceerd')
                                    : t('Concept')}
                            </Badge>
                            {court.lat == null || court.lng == null ? (
                                <Badge variant="secondary">
                                    {t('Binnenkort (geen pin)')}
                                </Badge>
                            ) : (
                                <Badge variant="secondary">
                                    {t('Actief op kaart')}
                                </Badge>
                            )}
                        </div>
                        <div className="grid gap-5">
                            <Field label={t('Titel')} value={title} />
                            {body ? (
                                <Field
                                    label={t('Beschrijving')}
                                    value={body}
                                    pre
                                />
                            ) : null}
                            {location ? (
                                <Field
                                    label={t('Locatie')}
                                    value={location}
                                />
                            ) : null}
                        </div>
                    </AdminPanel>

                    <AdminPanel
                        title={t('Instellingen')}
                        description={t('Kaartpositie en volgorde.')}
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                label={t('Coördinaten')}
                                value={
                                    court.lat != null && court.lng != null
                                        ? `${court.lat}, ${court.lng}`
                                        : t('Geen coördinaten')
                                }
                            />
                            <Field
                                label={t('Volgorde')}
                                value={String(court.sort_order)}
                            />
                        </div>
                    </AdminPanel>
                </AdminResourceShell>
            </div>
        </>
    );
}

CourtShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Club Corners', href: courtsRoutes.index(wayfinderLocale()) },
        { title: 'Gegevens', href: courtsRoutes.index(wayfinderLocale()) },
    ],
};
