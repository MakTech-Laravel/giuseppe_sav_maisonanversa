import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, MapPin, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
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

export default function CourtShow({ court }: { court: CourtDetail }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={court.title} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={court.title}
                    description={court.body ?? undefined}
                    icon={MapPin}
                >
                    <Button variant="outline" asChild>
                        <Link href={courtsRoutes.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={courtsRoutes.edit({
                                locale: wayfinderLocale(),
                                court: court.id,
                            })}
                        >
                            <Pencil className="h-4 w-4" /> {t('Bewerken')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <dl className="max-w-xl space-y-4 rounded-xl border bg-card p-6 text-sm shadow-sm">
                    <Detail label={t('Locatie')} value={court.location} />
                    <Detail
                        label={t('Coördinaten')}
                        value={
                            court.lat != null && court.lng != null
                                ? `${court.lat}, ${court.lng}`
                                : t('Binnenkort (geen pin)')
                        }
                    />
                    <Detail
                        label={t('Volgorde')}
                        value={String(court.sort_order)}
                    />
                    <Detail
                        label={t('Status')}
                        value={
                            court.is_published
                                ? t('Gepubliceerd')
                                : t('Concept')
                        }
                    />
                </dl>
            </div>
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-medium whitespace-pre-wrap">{value}</dd>
        </div>
    );
}

CourtShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Club Corners', href: courtsRoutes.index(wayfinderLocale()) },
        { title: 'Gegevens', href: courtsRoutes.index(wayfinderLocale()) },
    ],
};
