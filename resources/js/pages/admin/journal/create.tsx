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

export default function CreateJournalArticle() {
    const { t } = useTranslation();
    const form = useForm(journalRoutes.store(wayfinderLocale()), {
        slug: '',
        title: '',
        excerpt: '',
        body: '',
        cover_path: '',
        category: '',
        author: '',
        date_label: '',
        published_at: '',
        sort_order: 0,
    });

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
                <form
                    onSubmit={submit}
                    className="w-full max-w-2xl space-y-5 rounded-xl border bg-card p-6 shadow-sm md:p-8"
                >
                    <Field
                        id="title"
                        label={t('Titel')}
                        value={form.data.title}
                        onChange={(v) => form.setData('title', v)}
                        error={form.errors.title}
                    />
                    <Field
                        id="slug"
                        label={t('Slug')}
                        value={form.data.slug}
                        onChange={(v) => form.setData('slug', v)}
                        error={form.errors.slug}
                    />
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
                    <Field
                        id="cover_path"
                        label={t('Cover asset')}
                        value={form.data.cover_path}
                        onChange={(v) => form.setData('cover_path', v)}
                    />
                    <Field
                        id="category"
                        label={t('Categorie')}
                        value={form.data.category}
                        onChange={(v) => form.setData('category', v)}
                    />
                    <Field
                        id="author"
                        label={t('Auteur')}
                        value={form.data.author}
                        onChange={(v) => form.setData('author', v)}
                    />
                    <Field
                        id="date_label"
                        label={t('Datumlabel')}
                        value={form.data.date_label}
                        onChange={(v) => form.setData('date_label', v)}
                    />
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

function Field({
    id,
    label,
    value,
    onChange,
    error,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <div className="space-y-2">
            <Label htmlFor={id}>{label}</Label>
            <Input
                id={id}
                value={value}
                onChange={(e) => onChange(e.target.value)}
            />
            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}

CreateJournalArticle.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Journal', href: journalRoutes.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: journalRoutes.create(wayfinderLocale()) },
    ],
};
