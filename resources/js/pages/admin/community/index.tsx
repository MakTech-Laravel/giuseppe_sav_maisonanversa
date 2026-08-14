import { Head, useForm } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
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
import community from '@/routes/admin/community';

interface QueueItem {
    id: string;
    reporter?: string;
    reason?: string;
    status: string;
}

interface CommunityPostRow {
    id: string;
    author: string;
    content: string;
    is_official: boolean;
    status: string;
}

export default function CommunityIndex({
    items,
    posts = [],
}: {
    items: QueueItem[];
    posts?: CommunityPostRow[];
    communityConnected: boolean;
}) {
    const { t } = useTranslation();
    const form = useForm(community.official(wayfinderLocale()), {
        content: '',
    });

    function submitOfficial(event: FormEvent) {
        event.preventDefault();
        form.submit();
    }

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
                <form
                    onSubmit={submitOfficial}
                    className="space-y-3 rounded-xl border bg-card p-5 shadow-sm"
                >
                    <p className="text-sm font-medium">
                        {t('Officieel bericht')}
                    </p>
                    <textarea
                        value={form.data.content}
                        onChange={(event) =>
                            form.setData('content', event.target.value)
                        }
                        className="min-h-24 w-full rounded-md border px-3 py-2 text-sm"
                    />
                    <Button type="submit" disabled={form.processing}>
                        {t('Publiceren')}
                    </Button>
                </form>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Auteur')}</TableHead>
                                <TableHead>{t('Fragment')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {posts.map((post) => (
                                <TableRow key={post.id}>
                                    <TableCell className="font-medium">
                                        {post.author}
                                        {post.is_official && (
                                            <Badge
                                                className="ml-2"
                                                variant="secondary"
                                            >
                                                {t('Officieel')}
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="max-w-md truncate">
                                        {post.content}
                                    </TableCell>
                                    <TableCell>{t(post.status)}</TableCell>
                                </TableRow>
                            ))}
                            {items.map((item) => (
                                <TableRow key={`report-${item.id}`}>
                                    <TableCell>
                                        {item.reporter ?? item.id}
                                    </TableCell>
                                    <TableCell className="max-w-md truncate">
                                        {item.reason}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {t(item.status)}
                                        </Badge>
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
