import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { JournalArticleFormFields } from '@/components/admin/journal-article-form-fields';
import type { JournalArticleFormData } from '@/components/admin/journal-article-form-fields';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import journalRoutes from '@/routes/admin/journal';

const defaults: JournalArticleFormData = {
    slug: '',
    title: '',
    excerpt: '',
    body: '',
    cover_path: '',
    category: '',
    author: '',
    date_label: '',
    published_at: '',
    sort_order: '0',
    image: null,
    remove_image: false,
};

export default function CreateJournalArticle() {
    const { t } = useTranslation();
    const form = useForm(journalRoutes.store(wayfinderLocale()), defaults);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            published_at: data.published_at === '' ? null : data.published_at,
            sort_order: Number(data.sort_order),
        }));
        form.submit({ forceFormData: true });
    }

    return (
        <>
            <Head title={t('Journalartikel aanmaken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Journalartikel aanmaken')}
                    description={t('Schrijf een nieuw journalstuk.')}
                    icon={BookOpen}
                >
                    <Button variant="outline" asChild>
                        <Link href={journalRoutes.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <form onSubmit={submit} className="w-full space-y-6">
                    <JournalArticleFormFields
                        data={form.data}
                        errors={form.errors}
                        setData={form.setData}
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

CreateJournalArticle.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Journal', href: journalRoutes.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: journalRoutes.create(wayfinderLocale()) },
    ],
};
