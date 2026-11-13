import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CalendarPlus, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    capacityFromFormValue,
    CommunityEventFormFields,
} from '@/components/admin/community-event-form-fields';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import eventsRoutes from '@/routes/admin/events';

export default function CreateEvent() {
    const { t } = useTranslation();
    const form = useForm(eventsRoutes.store(wayfinderLocale()), {
        title: '',
        description: '',
        starts_at: '',
        location: '',
        capacity: '',
        thumbnail: null as File | null,
        remove_thumbnail: false,
    });

    function submit(event: FormEvent) {
        event.preventDefault();

        // Inertia's transform() does not return the form — do not chain.
        form.transform((data) => ({
            ...data,
            capacity: capacityFromFormValue(data.capacity),
        }));

        form.submit({ forceFormData: true });
    }

    return (
        <>
            <Head title={t('Create event')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Create event')}
                    description={t('Plan a new Maison event.')}
                    icon={CalendarPlus}
                >
                    <Button variant="outline" asChild>
                        <Link href={eventsRoutes.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Back to events')}
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
                    />
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {t('Create event')}
                    </Button>
                </form>
            </div>
        </>
    );
}

CreateEvent.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Evenementen', href: eventsRoutes.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: eventsRoutes.create(wayfinderLocale()) },
    ],
};
