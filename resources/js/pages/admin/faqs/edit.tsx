import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CircleHelp, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminResourceShell } from '@/components/admin/admin-resource-shell';
import {
    FaqFormAside,
    FaqFormFields,
    type FaqContextOption,
} from '@/components/admin/faq-form-fields';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import faqs from '@/routes/admin/faqs';

interface FaqEditProps {
    faq: {
        id: string;
        context: string;
        question: string;
        answer: string;
        sort_order: number;
        is_published: boolean;
    };
    contexts: FaqContextOption[];
}

export default function EditFaq({ faq, contexts }: FaqEditProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const form = useForm(
        faqs.update({
            locale,
            faq: Number(faq.id),
        }),
        {
            context: faq.context,
            question: faq.question,
            answer: faq.answer,
            sort_order: faq.sort_order,
            is_published: faq.is_published,
        },
    );

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
            <Head title={t('FAQ bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('FAQ bewerken')}
                    description={faq.question}
                    icon={CircleHelp}
                >
                    <Button variant="outline" asChild>
                        <Link
                            href={faqs.show({
                                locale,
                                faq: Number(faq.id),
                            })}
                        >
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <form onSubmit={submit} className="w-full space-y-6">
                    <AdminResourceShell
                        aside={
                            <FaqFormAside
                                isEdit
                                faq={{
                                    id: faq.id,
                                    context: form.data.context,
                                    is_published: form.data.is_published,
                                    sort_order: form.data.sort_order,
                                }}
                            />
                        }
                    >
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
                            <Link
                                href={faqs.show({
                                    locale,
                                    faq: Number(faq.id),
                                })}
                            >
                                {t('Annuleren')}
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

EditFaq.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'FAQ', href: faqs.index(wayfinderLocale()) },
        { title: 'Bewerken', href: '#' },
    ],
};
