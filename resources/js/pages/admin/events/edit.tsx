import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import eventsRoutes from '@/routes/admin/events';

interface EventEditProps {
    event: {
        id: string;
        title: string;
        description: string | null;
        starts_at: string;
        location: string | null;
        capacity: number | null;
    };
}

export default function EditEvent({ event }: EventEditProps) {
    const { t } = useTranslation();
    const form = useForm(
        eventsRoutes.update({
            locale: wayfinderLocale(),
            event: event.id,
        }),
        {
            title: event.title,
            description: event.description ?? '',
            starts_at: event.starts_at,
            location: event.location ?? '',
            capacity: event.capacity ?? ('' as string | number | null),
        },
    );

    function submit(formEvent: FormEvent) {
        formEvent.preventDefault();
        form
            .transform((data) => ({
                ...data,
                capacity:
                    data.capacity === '' || data.capacity === null
                        ? null
                        : Number(data.capacity),
            }))
            .submit();
    }

    return (
        <>
            <Head title={t('Evenement bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Evenement bewerken')}
                    description={event.title}
                    icon={CalendarDays}
                >
                    <Button variant="outline" asChild>
                        <Link
                            href={eventsRoutes.show({
                                locale: wayfinderLocale(),
                                event: event.id,
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
                        {form.errors.title && (
                            <p className="text-sm text-destructive">
                                {form.errors.title}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">{t('Beschrijving')}</Label>
                        <textarea
                            id="description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            className="min-h-28 w-full rounded-md border px-3 py-2 text-sm"
                        />
                        {form.errors.description && (
                            <p className="text-sm text-destructive">
                                {form.errors.description}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="starts_at">{t('Datum')}</Label>
                        <Input
                            id="starts_at"
                            type="datetime-local"
                            value={form.data.starts_at}
                            onChange={(e) =>
                                form.setData('starts_at', e.target.value)
                            }
                        />
                        {form.errors.starts_at && (
                            <p className="text-sm text-destructive">
                                {form.errors.starts_at}
                            </p>
                        )}
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
                        {form.errors.location && (
                            <p className="text-sm text-destructive">
                                {form.errors.location}
                            </p>
                        )}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="capacity">{t('Capaciteit')}</Label>
                        <Input
                            id="capacity"
                            type="number"
                            min={1}
                            value={form.data.capacity ?? ''}
                            onChange={(e) =>
                                form.setData('capacity', e.target.value)
                            }
                        />
                        {form.errors.capacity && (
                            <p className="text-sm text-destructive">
                                {form.errors.capacity}
                            </p>
                        )}
                    </div>
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

EditEvent.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Evenementen', href: eventsRoutes.index(wayfinderLocale()) },
        { title: 'Bewerken', href: eventsRoutes.index(wayfinderLocale()) },
    ],
};
