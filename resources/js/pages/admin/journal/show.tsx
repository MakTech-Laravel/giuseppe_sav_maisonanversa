import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import journalRoutes from '@/routes/admin/journal';

interface ArticleDetail {
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
}

export default function JournalShow({
    article,
}: {
    article: ArticleDetail;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={article.title} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={article.title}
                    description={article.excerpt}
                    icon={BookOpen}
                >
                    <Button variant="outline" asChild>
                        <Link href={journalRoutes.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={journalRoutes.edit({
                                locale: wayfinderLocale(),
                                article: article.id,
                            })}
                        >
                            <Pencil className="h-4 w-4" /> {t('Bewerken')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <dl className="max-w-3xl space-y-4 rounded-xl border bg-card p-6 text-sm shadow-sm">
                    <Detail label={t('Slug')} value={article.slug} />
                    <Detail
                        label={t('Categorie')}
                        value={article.category ?? '—'}
                    />
                    <Detail
                        label={t('Auteur')}
                        value={article.author ?? '—'}
                    />
                    <Detail
                        label={t('Datumlabel')}
                        value={article.date_label ?? '—'}
                    />
                    <Detail
                        label={t('Cover')}
                        value={article.cover_path ?? '—'}
                    />
                    <Detail
                        label={t('Body')}
                        value={article.body}
                    />
                </dl>
            </div>
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-medium whitespace-pre-wrap">{value}</dd>
        </div>
    );
}

JournalShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Journal', href: journalRoutes.index(wayfinderLocale()) },
        { title: 'Gegevens', href: journalRoutes.index(wayfinderLocale()) },
    ],
};
