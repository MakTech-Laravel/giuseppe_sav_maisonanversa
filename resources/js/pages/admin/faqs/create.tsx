import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CircleHelp, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminResourceShell } from '@/components/admin/admin-resource-shell';
import {
    FaqFormAside,
    FaqFormFields,
} from '@/components/admin/faq-form-fields';
import type { FaqContextOption } from '@/components/admin/faq-form-fields';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import faqs from '@/routes/admin/faqs';

export default function CreateFaq({
    contexts,
}: {
    contexts: FaqContextOption[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const form = useForm(faqs.store(locale), {
        context: contexts[0]?.value ?? 'product',
        question: '',
        answer: '',
        sort_order: 0,
        is_published: true,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            sort_order: Number(data.sort_order),
        }));
        form.submit();
    }

    return (
        <>
            <Head title={t('FAQ aanmaken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('FAQ aanmaken')}
                    description={t('Voeg een nieuwe veelgestelde vraag toe.')}
                    icon={CircleHelp}
                >
                    <Button variant="outline" asChild>
                        <Link href={faqs.index(locale)}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <form onSubmit={submit} className="w-full space-y-6">
                    <AdminResourceShell aside={<FaqFormAside />}>
                        <FaqFormFields
                            data={form.data}
                            errors={form.errors}
                            contexts={contexts}
                            setData={form.setData}
                        />
                    </AdminResourceShell>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : null}
                            {t('Opslaan')}
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={faqs.index(locale)}>
                                {t('Annuleren')}
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

CreateFaq.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'FAQ', href: faqs.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: faqs.create(wayfinderLocale()) },
    ],
};
