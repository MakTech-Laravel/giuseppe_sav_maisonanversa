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

interface ArticleEditProps {
    article: {
        id: string;
        slug: string;
        title: string;
        excerpt: string;
        body: string;
        cover_path: string | null;
        image_url: string | null;
        category: string | null;
        author: string | null;
        date_label: string | null;
        published_at: string | null;
        sort_order: number;
    };
}

export default function EditJournalArticle({ article }: ArticleEditProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    const formDef = journalRoutes.update.form({
        locale,
        article: article.id,
    });

    const form = useForm(
        {
            url: formDef.action,
            method: formDef.method,
        },
        {
            slug: article.slug,
            title: article.title,
            excerpt: article.excerpt,
            body: article.body,
            cover_path: article.cover_path ?? '',
            category: article.category ?? '',
            author: article.author ?? '',
            date_label: article.date_label ?? '',
            published_at: article.published_at ?? '',
            sort_order: String(article.sort_order),
            image: null as File | null,
            remove_image: false,
        } satisfies JournalArticleFormData,
    );

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
            <Head title={t('Journalartikel bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Journalartikel bewerken')}
                    description={article.title}
                    icon={BookOpen}
                >
                    <Button variant="outline" asChild>
                        <Link
                            href={journalRoutes.show({
                                locale: wayfinderLocale(),
                                article: article.id,
                            })}
                        >
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <form onSubmit={submit} className="w-full space-y-6">
                    <JournalArticleFormFields
                        data={form.data}
                        errors={form.errors}
                        setData={form.setData}
                        existingImageUrl={article.image_url}
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

EditJournalArticle.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Journal', href: journalRoutes.index(wayfinderLocale()) },
        { title: 'Bewerken', href: journalRoutes.index(wayfinderLocale()) },
    ],
};
