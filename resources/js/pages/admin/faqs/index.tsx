import { Head, Link } from '@inertiajs/react';
import { CircleHelp, Pencil, Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useLocale } from '@/hooks/use-locale';

interface FaqRow {
    id: string;
    context: string;
    question: string;
    answer: string;
    sort_order: number;
    is_published: boolean;
}

export default function FaqsIndex({ faqs }: { faqs: FaqRow[] }) {
    const { t } = useTranslation();
    const { locale } = useLocale();

    return (
        <>
            <Head title={t('FAQ')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title={t('FAQ')} description={t('Beheer veelgestelde vragen voor product en contact.')} icon={CircleHelp}>
                    <Button asChild>
                        <Link href={`/${locale}/admin/faqs/create`}>
                            <Plus className="h-4 w-4" /> {t('FAQ toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Vraag')}</TableHead>
                                <TableHead>{t('Context')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Volgorde')}</TableHead>
                                <TableHead className="text-right">{t('Acties')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {faqs.map((faq) => (
                                <TableRow key={faq.id}>
                                    <TableCell className="max-w-xl whitespace-normal font-medium">{faq.question}</TableCell>
                                    <TableCell>{faq.context}</TableCell>
                                    <TableCell>{faq.is_published ? t('Gepubliceerd') : t('Concept')}</TableCell>
                                    <TableCell>{faq.sort_order}</TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="ghost" size="icon" asChild>
                                            <Link href={`/${locale}/admin/faqs/${faq.id}/edit`} title={t('Bewerken')}>
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

FaqsIndex.layout = {
    title: 'FAQ',
};
