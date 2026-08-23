import { Head, router, useForm } from '@inertiajs/react';
import { MessageCircle, Search, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { DataPagination } from '@/components/admin/data-pagination';
import { PostEditDialog } from '@/components/admin/post-edit-dialog';
import { PostTranslationsDialog } from '@/components/admin/post-translations-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import type { Paginated } from '@/types/admin';

interface QueueItem {
    id: string;
    post_id?: number | string;
    reporter?: string;
    reason?: string;
    status: string;
}

type LocaleCopy = {
    content: string;
};

type TranslationStatus = {
    content: boolean;
};

interface CommunityPostRow {
    id: string;
    author: string;
    author_id: string;
    content: string;
    excerpt: string;
    is_truncated: boolean;
    is_official: boolean;
    status: string;
    can_edit: boolean;
    created_at: string | null;
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}

interface CommunityFilters {
    search: string;
    status: string;
    type: string;
}

function emptyPaginated(): Paginated<CommunityPostRow> {
    return {
        data: [],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        from: null,
        to: null,
        total: 0,
        links: [],
        prev_page_url: null,
        next_page_url: null,
    };
}

function normalizePosts(
    posts: Paginated<CommunityPostRow> | CommunityPostRow[] | undefined,
): Paginated<CommunityPostRow> {
    if (posts == null) {
        return emptyPaginated();
    }

    if (Array.isArray(posts)) {
        return {
            data: posts,
            current_page: 1,
            last_page: 1,
            per_page: posts.length || 15,
            from: posts.length > 0 ? 1 : null,
            to: posts.length > 0 ? posts.length : null,
            total: posts.length,
            links: [],
            prev_page_url: null,
            next_page_url: null,
        };
    }

    return {
        ...emptyPaginated(),
        ...posts,
        data: Array.isArray(posts.data) ? posts.data : [],
        links: Array.isArray(posts.links) ? posts.links : [],
    };
}

function communityQuery(
    filters: CommunityFilters,
): Record<string, string | undefined> {
    return {
        search: filters.search || undefined,
        status: filters.status || undefined,
        type: filters.type || undefined,
    };
}

export default function CommunityIndex({
    items = [],
    posts,
    filters = { search: '', status: '', type: '' },
    locales = ['nl', 'en', 'fr'],
}: {
    items?: QueueItem[];
    posts?: Paginated<CommunityPostRow> | CommunityPostRow[];
    filters?: CommunityFilters;
    locales?: string[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const paginated = normalizePosts(posts);
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [type, setType] = useState(filters.type || 'all');
    const firstRender = useRef(true);

    const form = useForm(community.official(locale), {
        content: '',
    });

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                community.index(locale).url,
                communityQuery({
                    search,
                    status: status === 'all' ? '' : status,
                    type: type === 'all' ? '' : type,
                }),
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['posts', 'filters'],
                },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, type, locale]);

    const hasActiveFilters =
        Boolean(search) || status !== 'all' || type !== 'all';

    const clearFilters = () => {
        setSearch('');
        setStatus('all');
        setType('all');
    };

    function submitOfficial(event: FormEvent) {
        event.preventDefault();
        form.submit();
    }

    function hidePost(postId: string) {
        router.post(
            community.hide({
                locale,
                communityPost: Number(postId),
            }).url,
            {},
            { preserveScroll: true },
        );
    }

    function resolveReport(reportId: string, reportStatus: 'resolved' | 'dismissed') {
        router.patch(
            community.reports.resolve({
                locale,
                communityReport: Number(reportId),
            }).url,
            { status: reportStatus },
            { preserveScroll: true },
        );
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
                        maxLength={2000}
                    />
                    <Button type="submit" disabled={form.processing}>
                        {t('Publiceren')}
                    </Button>
                </form>

                <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_11rem_11rem_auto] sm:items-center">
                    <div className="relative min-w-0">
                        <Label htmlFor="community-search" className="sr-only">
                            {t('Zoeken')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="community-search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Zoek op auteur of inhoud…')}
                            className="w-full pl-9"
                            aria-label={t('Zoeken')}
                        />
                    </div>
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Status')}
                        >
                            <SelectValue placeholder={t('Alle statussen')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('Alle statussen')}
                            </SelectItem>
                            <SelectItem value="published">
                                {t('Gepubliceerd')}
                            </SelectItem>
                            <SelectItem value="hidden">
                                {t('Verborgen')}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={type} onValueChange={setType}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Type')}
                        >
                            <SelectValue placeholder={t('Alle types')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('Alle types')}
                            </SelectItem>
                            <SelectItem value="official">
                                {t('Officieel')}
                            </SelectItem>
                            <SelectItem value="member">
                                {t('Lid')}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    {hasActiveFilters ? (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="justify-self-start"
                            onClick={clearFilters}
                        >
                            <X className="h-4 w-4" />
                            {t('Wis filters')}
                        </Button>
                    ) : (
                        <span className="hidden sm:block" aria-hidden />
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Auteur')}</TableHead>
                                <TableHead>{t('Fragment')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={4}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('Geen berichten gevonden.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((post) => (
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
                                        <TableCell className="max-w-md">
                                            <span className="line-clamp-2 text-sm text-muted-foreground">
                                                {post.excerpt}
                                                {post.is_truncated ? '…' : ''}
                                            </span>
                                        </TableCell>
                                        <TableCell>{t(post.status)}</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex flex-wrap justify-end gap-2">
                                                {post.can_edit ? (
                                                    <>
                                                        <PostEditDialog
                                                            postId={post.id}
                                                            content={post.content}
                                                        />
                                                        <PostTranslationsDialog
                                                            postId={post.id}
                                                            locales={locales}
                                                            translations={
                                                                post.translations
                                                            }
                                                            translationStatus={
                                                                post.translationStatus
                                                            }
                                                        />
                                                    </>
                                                ) : (
                                                    post.status !== 'hidden' && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            type="button"
                                                            onClick={() =>
                                                                hidePost(post.id)
                                                            }
                                                        >
                                                            {t('Verbergen')}
                                                        </Button>
                                                    )
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                    <DataPagination meta={paginated} />
                </div>

                {items.length > 0 && (
                    <div className="space-y-3">
                        <h2 className="text-sm font-medium">
                            {t('Meldingen')}
                        </h2>
                        <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/50 hover:bg-muted/50">
                                        <TableHead>{t('Melder')}</TableHead>
                                        <TableHead>{t('Reden')}</TableHead>
                                        <TableHead>{t('Status')}</TableHead>
                                        <TableHead className="text-right">
                                            {t('Acties')}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
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
                                            <TableCell className="space-x-2 text-right">
                                                {item.status === 'open' && (
                                                    <>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            type="button"
                                                            onClick={() =>
                                                                resolveReport(
                                                                    item.id,
                                                                    'resolved',
                                                                )
                                                            }
                                                        >
                                                            {t('Oplossen')}
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            type="button"
                                                            onClick={() =>
                                                                resolveReport(
                                                                    item.id,
                                                                    'dismissed',
                                                                )
                                                            }
                                                        >
                                                            {t('Afwijzen')}
                                                        </Button>
                                                    </>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                )}
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
