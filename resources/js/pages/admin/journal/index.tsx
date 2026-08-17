import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import journalRoutes from '@/routes/admin/journal';

interface ArticleRow {
    id: string;
    slug: string;
    title: string;
    sort_order: number;
    published_at: string | null;
}

export default function JournalIndex({
    articles,
}: {
    articles: ArticleRow[];
}) {
    const { t } = useTranslation();

    function destroyArticle(articleId: string) {
        if (!window.confirm(t('Artikel verwijderen?'))) {
            return;
        }

        router.delete(
            journalRoutes.destroy({
                locale: wayfinderLocale(),
                article: articleId,
            }).url,
        );
    }

    return (
        <>
            <Head title={t('Journal')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Journal')}
                    description={t('Beheer redactionele journalartikelen.')}
                    icon={BookOpen}
                >
                    <Button asChild>
                        <Link href={journalRoutes.create(wayfinderLocale())}>
                            <Plus className="h-4 w-4" /> {t('Artikel toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Titel')}</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Slug')}
                                </TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {articles.map((article) => (
                                <TableRow key={article.id}>
                                    <TableCell className="font-medium">
                                        {article.title}
                                    </TableCell>
                                    <TableCell className="hidden md:table-cell">
                                        {article.slug}
                                    </TableCell>
                                    <TableCell>
                                        {article.published_at
                                            ? t('Gepubliceerd')
                                            : t('Concept')}
                                    </TableCell>
                                    <TableCell className="space-x-1 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                        >
                                            <Link
                                                href={journalRoutes.show({
                                                    locale: wayfinderLocale(),
                                                    article: article.id,
                                                })}
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                        >
                                            <Link
                                                href={journalRoutes.edit({
                                                    locale: wayfinderLocale(),
                                                    article: article.id,
                                                })}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            type="button"
                                            onClick={() =>
                                                destroyArticle(article.id)
                                            }
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

JournalIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Journal', href: journalRoutes.index(wayfinderLocale()) },
    ],
};
