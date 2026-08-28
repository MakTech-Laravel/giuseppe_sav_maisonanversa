import { Link, router } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Eye, EyeOff, Search, Trash2, X } from 'lucide-react';
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
import type { Paginated } from '@/types/admin';
import type {
    InquiryFilters,
    InquiryKindOption,
    InquiryListItem,
    InquiryRouteHelpers,
} from '@/types/inquiry';

const DEFAULT_PER_PAGE = 15;

function inquiryQuery(
    filters: InquiryFilters,
    showKind: boolean,
): Record<string, string | number | undefined> {
    return {
        search: filters.search || undefined,
        status: filters.status || undefined,
        kind: showKind ? filters.kind || undefined : undefined,
        per_page:
            filters.per_page === DEFAULT_PER_PAGE
                ? undefined
                : filters.per_page,
    };
}

export function InquiryInbox({
    title,
    description,
    icon: Icon,
    inquiries: paginated,
    filters,
    perPageOptions = [10, 15, 25, 50, 100],
    kinds = [],
    routes,
    emptyLabel,
}: {
    title: string;
    description: string;
    icon: LucideIcon;
    inquiries: Paginated<InquiryListItem>;
    filters: InquiryFilters;
    perPageOptions?: number[];
    kinds?: InquiryKindOption[];
    routes: InquiryRouteHelpers;
    emptyLabel: string;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const showKind = kinds.length > 0;
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [kind, setKind] = useState(filters.kind || 'all');
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
                routes.index(locale).url,
                inquiryQuery(
                    {
                        search,
                        status: status === 'all' ? '' : status,
                        kind: kind === 'all' ? '' : kind,
                        per_page: perPage,
                    },
                    showKind,
                ),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, kind, perPage, locale, routes, showKind]);

    const hasActiveFilters =
        Boolean(search) || status !== 'all' || kind !== 'all';

    function toggleSeen(inquiry: InquiryListItem) {
        router.patch(
            routes.seen({
                locale,
                inquiry: Number(inquiry.id),
            }).url,
            { seen: !inquiry.seen },
            { preserveScroll: true },
        );
    }

    const columnCount = showKind ? 7 : 6;

    return (
        <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <AdminPageHeader
                title={title}
                description={description}
                icon={Icon}
            />

            <div
                className={`grid gap-3 sm:grid-cols-2 ${showKind ? 'lg:grid-cols-4 xl:grid-cols-5' : 'lg:grid-cols-3 xl:grid-cols-4'}`}
            >
                <div className="relative sm:col-span-2">
                    <Label htmlFor="inquiry-search" className="sr-only">
                        {t('Zoeken')}
                    </Label>
                    <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        id="inquiry-search"
                        className="pl-9"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={t('Zoek op e-mail of naam…')}
                        aria-label={t('Zoeken')}
                    />
                </div>
                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger className="w-full" aria-label={t('Status')}>
                        <SelectValue placeholder={t('Alle statussen')} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">
                            {t('Alle statussen')}
                        </SelectItem>
                        <SelectItem value="unseen">{t('Ongelezen')}</SelectItem>
                        <SelectItem value="seen">{t('Gezien')}</SelectItem>
                    </SelectContent>
                </Select>
                {showKind ? (
                    <Select value={kind} onValueChange={setKind}>
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
                            {kinds.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {t(option.label)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ) : null}
                <Select
                    value={String(perPage)}
                    onValueChange={(value) => setPerPage(Number(value))}
                >
                    <SelectTrigger
                        className="w-full"
                        aria-label={t('Per pagina')}
                    >
                        <SelectValue placeholder={t('Per pagina')} />
                    </SelectTrigger>
                    <SelectContent>
                        {perPageOptions.map((option) => (
                            <SelectItem key={option} value={String(option)}>
                                {t('{{count}} per pagina', {
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
                        onClick={() => {
                            setSearch('');
                            setStatus('all');
                            setKind('all');
                        }}
                        className="w-full sm:col-span-2 xl:w-auto xl:justify-self-start"
                    >
                        <X className="h-4 w-4" />
                        {t('Filters wissen')}
                    </Button>
                ) : null}
            </div>

            <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-muted/50 hover:bg-muted/50">
                            <TableHead>{t('Naam')}</TableHead>
                            <TableHead>{t('E-mail')}</TableHead>
                            {showKind ? (
                                <TableHead>{t('Type')}</TableHead>
                            ) : null}
                            <TableHead>{t('Bericht')}</TableHead>
                            <TableHead>{t('Status')}</TableHead>
                            <TableHead>{t('Datum')}</TableHead>
                            <TableHead className="text-right">
                                {t('Acties')}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {paginated.data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={columnCount}
                                    className="py-10 text-center text-sm text-muted-foreground"
                                >
                                    {emptyLabel}
                                </TableCell>
                            </TableRow>
                        ) : (
                            paginated.data.map((inquiry) => (
                                <TableRow key={inquiry.id}>
                                    <TableCell className="font-medium">
                                        {inquiry.name}
                                    </TableCell>
                                    <TableCell>{inquiry.email}</TableCell>
                                    {showKind ? (
                                        <TableCell>
                                            {t(inquiry.type_label)}
                                        </TableCell>
                                    ) : null}
                                    <TableCell className="max-w-xs">
                                        <div className="line-clamp-2 text-sm text-muted-foreground">
                                            {inquiry.message || '—'}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                inquiry.seen
                                                    ? 'secondary'
                                                    : 'default'
                                            }
                                        >
                                            {inquiry.seen
                                                ? t('Gezien')
                                                : t('Ongelezen')}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap text-muted-foreground">
                                        {inquiry.created_at
                                            ? new Date(
                                                  inquiry.created_at,
                                              ).toLocaleString()
                                            : '—'}
                                    </TableCell>
                                    <TableCell className="space-x-2 text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={routes.show({
                                                    locale,
                                                    inquiry: Number(inquiry.id),
                                                })}
                                            >
                                                <Eye className="h-4 w-4" />
                                                {t('Bekijken')}
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => toggleSeen(inquiry)}
                                        >
                                            {inquiry.seen ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                            {inquiry.seen
                                                ? t('Ongelezen')
                                                : t('Gezien')}
                                        </Button>
                                        <ConfirmDeleteDialog
                                            description={t(
                                                'Deze aanvraag wordt permanent verwijderd.',
                                            )}
                                            onConfirm={() =>
                                                router.delete(
                                                    routes.destroy({
                                                        locale,
                                                        inquiry: Number(
                                                            inquiry.id,
                                                        ),
                                                    }).url,
                                                )
                                            }
                                        >
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                                {t('Verwijderen')}
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
    );
}
