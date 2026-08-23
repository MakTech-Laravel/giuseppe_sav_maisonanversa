import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    capacityFromFormValue,
    capacityToFormValue,
    CommunityEventFormFields,
} from '@/components/admin/community-event-form-fields';
import { Button } from '@/components/ui/button';
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
        thumbnail_url: string | null;
    };
}

function multipartUpdateEndpoint(
    locale: string,
    eventId: string,
): { url: string; method: 'post' } {
    const formDef = eventsRoutes.update.form({
        locale,
        event: eventId,
    });

    return {
        url: formDef.action,
        method: formDef.method,
    };
}

export default function EditEvent({ event }: EventEditProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    // Wayfinder `.form()` spoofs PUT via POST + `?_method=PUT` so multipart
    // bodies are parsed by PHP (native PUT + FormData arrives empty).
    const form = useForm(multipartUpdateEndpoint(locale, event.id), {
        title: event.title,
        description: event.description ?? '',
        starts_at: event.starts_at,
        location: event.location ?? '',
        capacity: capacityToFormValue(event.capacity),
        thumbnail: null as File | null,
        remove_thumbnail: false,
    });

    function submit(formEvent: FormEvent) {
        formEvent.preventDefault();

        // Inertia's transform() does not return the form — do not chain.
        form.transform((data) => {
            const payload: Record<string, unknown> = {
                title: data.title,
                description: data.description,
                starts_at: data.starts_at,
                location: data.location,
                capacity: capacityFromFormValue(data.capacity),
                remove_thumbnail: data.remove_thumbnail,
            };

            if (data.thumbnail instanceof File) {
                payload.thumbnail = data.thumbnail;
            }

            return payload;
        });

        form.submit({ forceFormData: true });
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
                    className="w-full space-y-5 rounded-xl border bg-card p-6 shadow-sm md:p-8"
                >
                    <CommunityEventFormFields
                        data={form.data}
                        errors={form.errors}
                        setData={form.setData}
                        existingThumbnailUrl={event.thumbnail_url}
                    />
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
