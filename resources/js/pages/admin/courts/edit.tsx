import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, MapPin } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import courtsRoutes from '@/routes/admin/courts';

interface CourtEditProps {
    court: {
        id: string;
        title: string;
        body: string | null;
        location: string | null;
        lat: string;
        lng: string;
        sort_order: number;
        is_published: boolean;
    };
}

export default function EditCourt({ court }: CourtEditProps) {
    const { t } = useTranslation();
    const form = useForm(
        courtsRoutes.update({
            locale: wayfinderLocale(),
            court: court.id,
        }),
        {
            title: court.title,
            body: court.body ?? '',
            location: court.location ?? '',
            lat: court.lat,
            lng: court.lng,
            sort_order: court.sort_order,
            is_published: court.is_published,
        },
    );

    function submit(event: FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            lat: data.lat === '' ? null : Number(data.lat),
            lng: data.lng === '' ? null : Number(data.lng),
            sort_order: Number(data.sort_order),
        }));

        form.submit();
    }

    return (
        <>
            <Head title={t('Club Corner bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Club Corner bewerken')}
                    description={court.title}
                    icon={MapPin}
                >
                    <Button variant="outline" asChild>
                        <Link
                            href={courtsRoutes.show({
                                locale: wayfinderLocale(),
                                court: court.id,
                            })}
                        >
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <form
                    onSubmit={submit}
                    className="w-full max-w-2xl space-y-5 rounded-xl border bg-card p-6 shadow-sm md:p-8"
                >
                    <div className="space-y-2">
                        <Label htmlFor="title">{t('Titel')}</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="body">{t('Beschrijving')}</Label>
                        <textarea
                            id="body"
                            value={form.data.body}
                            onChange={(e) =>
                                form.setData('body', e.target.value)
                            }
                            className="min-h-28 w-full rounded-md border px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="location">{t('Locatie')}</Label>
                        <Input
                            id="location"
                            value={form.data.location}
                            onChange={(e) =>
                                form.setData('location', e.target.value)
                            }
                        />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="lat">{t('Latitude')}</Label>
                            <Input
                                id="lat"
                                value={form.data.lat}
                                onChange={(e) =>
                                    form.setData('lat', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="lng">{t('Longitude')}</Label>
                            <Input
                                id="lng"
                                value={form.data.lng}
                                onChange={(e) =>
                                    form.setData('lng', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="sort_order">{t('Volgorde')}</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={form.data.sort_order}
                            onChange={(e) =>
                                form.setData(
                                    'sort_order',
                                    Number(e.target.value),
                                )
                            }
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.is_published}
                            onChange={(e) =>
                                form.setData('is_published', e.target.checked)
                            }
                        />
                        {t('Gepubliceerd')}
                    </label>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {t('Opslaan')}
                    </Button>
                </form>
            </div>
        </>
    );
}

EditCourt.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Club Corners', href: courtsRoutes.index(wayfinderLocale()) },
        { title: 'Bewerken', href: courtsRoutes.index(wayfinderLocale()) },
    ],
};
