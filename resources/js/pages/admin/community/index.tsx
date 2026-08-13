import { Head } from '@inertiajs/react';
import { MessageCircle, TriangleAlert } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
import community from '@/routes/admin/community';

interface QueueItem {
    id: string;
    author: string;
    excerpt: string;
    type: string;
    status: string;
    status_key: string;
    date: string;
}

function translateQueueType(
    type: string,
    t: (key: string) => string,
): string {
    const typeMap: Record<string, string> = {
        post: 'Bericht',
        comment: 'Reactie',
    };

    return t(typeMap[type] ?? type);
}

function translateQueueStatus(
    status: string,
    t: (key: string) => string,
): string {
    const statusMap: Record<string, string> = {
        Open: 'Open',
        Reported: 'Gemeld',
        Approved: 'Goedgekeurd',
    };

    return t(statusMap[status] ?? status);
}

export default function CommunityIndex({
    items,
    communityConnected,
}: {
    items: QueueItem[];
    communityConnected: boolean;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Gemeenschap')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Gemeenschap')}
                    description={t(
                        'Moderatie van ledenconversaties en activiteit.',
                    )}
                    icon={MessageCircle}
                />
                {!communityConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>
                            {t('Communityservice is niet gekoppeld')}
                        </AlertTitle>
                        <AlertDescription>
                            {t(
                                'Deze moderatiewachtrij bevat demogegevens totdat de communityservice is geconfigureerd.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Auteur')}</TableHead>
                                <TableHead>{t('Fragment')}</TableHead>
                                <TableHead>{t('Type')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    {t('Datum')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {items.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell>
                                        <span className="font-medium">
                                            {item.author}
                                        </span>
                                        <p className="text-xs text-muted-foreground">
                                            {item.id}
                                        </p>
                                    </TableCell>
                                    <TableCell className="max-w-md truncate">
                                        {item.excerpt}
                                    </TableCell>
                                    <TableCell>
                                        {translateQueueType(item.type, t)}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {translateQueueStatus(
                                                item.status,
                                                t,
                                            )}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="hidden sm:table-cell">
                                        {item.date}
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

CommunityIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Gemeenschap', href: community.index(wayfinderLocale()) },
    ],
};
