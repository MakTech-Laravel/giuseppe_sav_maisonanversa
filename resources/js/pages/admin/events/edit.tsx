import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarDays, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect } from 'react';
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
import { isLocale, SOURCE_LOCALE } from '@/types/locale';
import type { Locale } from '@/types/locale';

type LocaleCopy = {
    title: string;
    description: string;
    location: string;
};

interface EventEditProps {
    event: {
        id: string;
        starts_at: string;
        capacity: number | null;
        thumbnail_url: string | null;
    };
    source: LocaleCopy;
    defaultLocale: string;
    translations: Record<string, LocaleCopy>;
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

function localeCopyForForm(
    locale: Locale,
    defaultLocale: string,
    source: LocaleCopy,
    translations: Record<string, LocaleCopy>,
): LocaleCopy {
    if (locale === defaultLocale) {
        return source;
    }

    return translations[locale] ?? source;
}

export default function EditEvent({
    event,
    source,
    defaultLocale,
    translations,
}: EventEditProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const localizedCopy = localeCopyForForm(
        isLocale(locale) ? locale : SOURCE_LOCALE,
        defaultLocale,
        source,
        translations,
    );

    const form = useForm(multipartUpdateEndpoint(locale, event.id), {
        title: localizedCopy.title,
        description: localizedCopy.description,
        starts_at: event.starts_at,
        location: localizedCopy.location,
        capacity: capacityToFormValue(event.capacity),
        thumbnail: null as File | null,
        remove_thumbnail: false,
    });

    useEffect(() => {
        const copy = localeCopyForForm(
            isLocale(locale) ? locale : SOURCE_LOCALE,
            defaultLocale,
            source,
            translations,
        );

        form.setData((current) => ({
            ...current,
            title: copy.title,
            description: copy.description,
            location: copy.location,
            starts_at: event.starts_at,
            capacity: capacityToFormValue(event.capacity),
            thumbnail: null,
            remove_thumbnail: false,
        }));
    }, [
        locale,
        defaultLocale,
        source.title,
        source.description,
        source.location,
        translations.en?.title,
        translations.en?.description,
        translations.en?.location,
        translations.fr?.title,
        translations.fr?.description,
        translations.fr?.location,
        event.starts_at,
        event.capacity,
    ]);

    function submit(formEvent: FormEvent) {
        formEvent.preventDefault();

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
                    description={localizedCopy.title}
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
