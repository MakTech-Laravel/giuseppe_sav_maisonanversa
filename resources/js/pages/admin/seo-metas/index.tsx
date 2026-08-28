import { Head, Link } from '@inertiajs/react';
import { Eye, Globe, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import seoMetas from '@/routes/admin/seo-metas';

interface SeoMetaRow {
    id: string;
    page_key: string;
    title: string;
    description: string;
    has_pending_translations: boolean;
}

export default function SeoMetasIndex({ rows }: { rows: SeoMetaRow[] }) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    return (
        <>
            <Head title={t('SEO Meta')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('SEO Meta')}
                    description={t(
                        'Beheer SEO-titels en beschrijvingen per pagina.',
                    )}
                    icon={Globe}
                />
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Pagina')}</TableHead>
                                <TableHead>{t('Titel')}</TableHead>
                                <TableHead>{t('Beschrijving')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell className="font-mono text-sm font-medium">
                                        {row.page_key}
                                        {row.has_pending_translations ? (
                                            <Badge
                                                variant="secondary"
                                                className="mt-1 block w-fit"
                                            >
                                                {t('Vertalingen ontbreken')}
                                            </Badge>
                                        ) : null}
                                    </TableCell>
                                    <TableCell className="max-w-sm whitespace-normal">
                                        {row.title}
                                    </TableCell>
                                    <TableCell className="max-w-md whitespace-normal text-muted-foreground">
                                        {row.description}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                        >
                                            <Link
                                                href={seoMetas.show({
                                                    locale,
                                                    seoMeta: Number(row.id),
                                                })}
                                                title={t('Voorbeeld')}
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
                                                href={seoMetas.edit({
                                                    locale,
                                                    seoMeta: Number(row.id),
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

SeoMetasIndex.layout = {
    title: 'SEO Meta',
};
