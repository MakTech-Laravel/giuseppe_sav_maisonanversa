import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        category: string | null;
        author: string | null;
        date_label: string | null;
        published_at: string | null;
        sort_order: number;
    };
}

export default function EditJournalArticle({ article }: ArticleEditProps) {
    const { t } = useTranslation();
    const form = useForm(
        journalRoutes.update({
            locale: wayfinderLocale(),
            article: article.id,
        }),
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
            sort_order: article.sort_order,
        },
    );

    function submit(event: FormEvent) {
        event.preventDefault();
        form
            .transform((data) => ({
                ...data,
                published_at:
                    data.published_at === '' ? null : data.published_at,
                sort_order: Number(data.sort_order),
            }))
            .submit();
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
                        <Label htmlFor="slug">{t('Slug')}</Label>
                        <Input
                            id="slug"
                            value={form.data.slug}
                            onChange={(e) =>
                                form.setData('slug', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="excerpt">{t('Excerpt')}</Label>
                        <textarea
                            id="excerpt"
                            value={form.data.excerpt}
                            onChange={(e) =>
                                form.setData('excerpt', e.target.value)
                            }
                            className="min-h-20 w-full rounded-md border px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="body">{t('Body')}</Label>
                        <textarea
                            id="body"
                            value={form.data.body}
                            onChange={(e) =>
                                form.setData('body', e.target.value)
                            }
                            className="min-h-40 w-full rounded-md border px-3 py-2 text-sm"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="cover_path">{t('Cover asset')}</Label>
                        <Input
                            id="cover_path"
                            value={form.data.cover_path}
                            onChange={(e) =>
                                form.setData('cover_path', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="category">{t('Categorie')}</Label>
                        <Input
                            id="category"
                            value={form.data.category}
                            onChange={(e) =>
                                form.setData('category', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="author">{t('Auteur')}</Label>
                        <Input
                            id="author"
                            value={form.data.author}
                            onChange={(e) =>
                                form.setData('author', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="date_label">{t('Datumlabel')}</Label>
                        <Input
                            id="date_label"
                            value={form.data.date_label}
                            onChange={(e) =>
                                form.setData('date_label', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="published_at">{t('Publicatiedatum')}</Label>
                        <Input
                            id="published_at"
                            type="datetime-local"
                            value={form.data.published_at}
                            onChange={(e) =>
                                form.setData('published_at', e.target.value)
                            }
                        />
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
