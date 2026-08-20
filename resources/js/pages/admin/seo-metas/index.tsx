import { Head, Link } from '@inertiajs/react';
import { Globe, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useLocale } from '@/hooks/use-locale';

interface SeoMetaRow {
    id: string;
    page_key: string;
    title: string;
    description: string;
}

export default function SeoMetasIndex({ rows }: { rows: SeoMetaRow[] }) {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <>
            <Head title={t('SEO Meta')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title={t('SEO Meta')} description={t('Beheer SEO-titels en beschrijvingen per pagina.')} icon={Globe} />
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Pagina')}</TableHead>
                                <TableHead>{t('Titel')}</TableHead>
                                <TableHead className="text-right">{t('Acties')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell className="font-medium">{row.page_key}</TableCell>
                                    <TableCell className="max-w-xl whitespace-normal">{row.title}</TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="icon" asChild>
                                            <Link href={`/${locale}/admin/seo-metas/${row.id}/edit`} title={t('Bewerken')}>
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

SeoMetasIndex.layout = {
    title: 'SEO Meta',
};
