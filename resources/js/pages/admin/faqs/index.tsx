import { Head, Link, router } from '@inertiajs/react';
import { CircleHelp, Eye, Pencil, Plus, Search, Trash2, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { DataPagination } from '@/components/admin/data-pagination';
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
import faqs from '@/routes/admin/faqs';
import type { Paginated } from '@/types/admin';

interface FaqListItem {
    id: string;
    context: string;
    question: string;
    answer: string;
    sort_order: number;
    is_published: boolean;
}

interface FaqFilters {
    search: string;
    context: string;
    status: string;
    per_page: number;
}

interface ContextOption {
    value: string;
    label: string;
}

const DEFAULT_PER_PAGE = 15;

function faqQuery(
    filters: FaqFilters,
): Record<string, string | number | undefined> {
    return {
        search: filters.search || undefined,
        context: filters.context || undefined,
        status: filters.status || undefined,
        per_page:
            filters.per_page === DEFAULT_PER_PAGE
                ? undefined
                : filters.per_page,
    };
}

export default function FaqsIndex({
    faqs: paginated,
    filters,
    perPageOptions = [10, 15, 25, 50, 100],
    contexts = [],
}: {
    faqs: Paginated<FaqListItem>;
    filters: FaqFilters;
    perPageOptions?: number[];
    contexts?: ContextOption[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [context, setContext] = useState(filters.context || 'all');
    const [status, setStatus] = useState(filters.status || 'all');
    const [perPage, setPerPage] = useState(
        filters.per_page ?? DEFAULT_PER_PAGE,
    );
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                faqs.index(locale).url,
                faqQuery({
                    search,
                    context: context === 'all' ? '' : context,
                    status: status === 'all' ? '' : status,
                    per_page: perPage,
                }),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, context, status, perPage, locale]);

    const hasActiveFilters =
        Boolean(search) || context !== 'all' || status !== 'all';

    const clearFilters = () => {
        setSearch('');
        setContext('all');
        setStatus('all');
    };

    const contextLabel = (value: string) =>
        value === 'product'
            ? t('Product')
            : value === 'contact'
              ? t('Contact')
              : value;

    return (
        <>
            <Head title={t('FAQ')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('FAQ')}
                    description={t(
                        'Manage frequently asked questions for product and contact.',
                    )}
                    icon={CircleHelp}
                >
                    <Button asChild>
                        <Link href={faqs.create(locale)}>
                            <Plus className="h-4 w-4" /> {t('Add FAQ')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    <div className="relative sm:col-span-2">
                        <Label htmlFor="faq-search" className="sr-only">
                            {t('Search')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="faq-search"
                            className="pl-9"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Search by question or answer…')}
                            aria-label={t('Search')}
                        />
                    </div>
                    <Select value={context} onValueChange={setContext}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Context')}
                        >
                            <SelectValue placeholder={t('All contexts')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('All contexts')}
                            </SelectItem>
                            {contexts.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {contextLabel(option.value)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Status')}
                        >
                            <SelectValue placeholder={t('All statuses')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('All statuses')}
                            </SelectItem>
                            <SelectItem value="published">
                                {t('Published')}
                            </SelectItem>
                            <SelectItem value="draft">
                                {t('Draft')}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={String(perPage)}
                        onValueChange={(value) => setPerPage(Number(value))}
                    >
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Per page')}
                        >
                            <SelectValue placeholder={t('Per page')} />
                        </SelectTrigger>
                        <SelectContent>
                            {perPageOptions.map((option) => (
                                <SelectItem key={option} value={String(option)}>
                                    {t('{{count}} per page', {
                                        count: option,
                                    })}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {hasActiveFilters ? (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={clearFilters}
                            className="w-full sm:col-span-2 lg:col-span-4 xl:col-span-5 xl:w-auto xl:justify-self-start"
                        >
                            <X className="h-4 w-4" />
                            {t('Clear filters')}
                        </Button>
                    ) : null}
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Question')}</TableHead>
                                <TableHead>{t('Context')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Order')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Actions')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('No FAQs found.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((faq) => (
                                    <TableRow key={faq.id}>
                                        <TableCell className="max-w-xl whitespace-normal">
                                            <div className="font-medium">
                                                {faq.question}
                                            </div>
                                            <div className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                                {faq.answer}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {contextLabel(faq.context)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {faq.is_published
                                                    ? t('Published')
                                                    : t('Draft')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{faq.sort_order}</TableCell>
                                        <TableCell className="space-x-2 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={faqs.show({
                                                        locale,
                                                        faq: Number(faq.id),
                                                    })}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                    {t('View')}
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={faqs.edit({
                                                        locale,
                                                        faq: Number(faq.id),
                                                    })}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    {t('Edit')}
                                                </Link>
                                            </Button>
                                            <ConfirmDeleteDialog
                                                description={t(
                                                    'This FAQ will be permanently deleted.',
                                                )}
                                                onConfirm={() =>
                                                    router.delete(
                                                        faqs.destroy({
                                                            locale,
                                                            faq: Number(faq.id),
                                                        }).url,
                                                    )
                                                }
                                            >
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    {t('Remove')}
                                                </Button>
                                            </ConfirmDeleteDialog>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
                <DataPagination meta={paginated} />
            </div>
        </>
    );
}

FaqsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'FAQ', href: faqs.index(wayfinderLocale()) },
    ],
};
