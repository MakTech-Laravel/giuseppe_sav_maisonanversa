import { Head, Link } from '@inertiajs/react';
import { Eye, FileText, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useLocale } from '@/hooks/use-locale';
import legalPages from '@/routes/admin/legal-pages';

interface LegalPageRow {
    id: string;
    slug: string;
    is_published: boolean;
}

export default function LegalPagesIndex({ pages }: { pages: LegalPageRow[] }) {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <>
            <Head title={t("Juridische Pagina's")} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title={t("Juridische Pagina's")} description={t('Beheer privacy-, voorwaarden-, verzending- en care-pagina’s.')} icon={FileText} />
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Slug')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">{t('Acties')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pages.map((page) => (
                                <TableRow key={page.id}>
                                    <TableCell className="font-medium">{page.slug}</TableCell>
                                    <TableCell>{page.is_published ? t('Gepubliceerd') : t('Concept')}</TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="icon" asChild>
                                            <Link
                                                href={legalPages.show({
                                                    locale,
                                                    legalPage: Number(page.id),
                                                })}
                                                title={t('Voorbeeld')}
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="icon" asChild>
                                            <Link
                                                href={legalPages.edit({
                                                    locale,
                                                    legalPage: Number(page.id),
                                                })}
                                                title={t('Bewerken')}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Link>
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

LegalPagesIndex.layout = {
    title: "Juridische Pagina's",
};
