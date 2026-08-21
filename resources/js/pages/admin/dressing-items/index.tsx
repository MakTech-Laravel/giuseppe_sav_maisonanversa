import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Shirt } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useLocale } from '@/hooks/use-locale';

interface DressingItemRow {
    id: string;
    name: string;
    slug: string;
    category: string;
    status: string;
    sort_order: number;
    is_published: boolean;
}

export default function DressingItemsIndex({ items }: { items: DressingItemRow[] }) {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <>
            <Head title={t('Kleedkamer')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title={t('Kleedkamer')} description={t('Beheer preview-items voor de Dressing Room.')} icon={Shirt}>
                    <Button asChild>
                        <Link href={`/${locale}/admin/dressing-items/create`}>
                            <Plus className="h-4 w-4" /> {t('Item toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Naam')}</TableHead>
                                <TableHead>{t('Categorie')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Volgorde')}</TableHead>
                                <TableHead className="text-right">{t('Acties')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">{item.name}</TableCell>
                                    <TableCell>{item.category}</TableCell>
                                    <TableCell>{item.status}</TableCell>
                                    <TableCell>{item.sort_order}</TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="icon" asChild>
                                            <Link href={`/${locale}/admin/dressing-items/${item.id}/edit`} title={t('Bewerken')}>
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

DressingItemsIndex.layout = {
    title: 'Kleedkamer',
};
