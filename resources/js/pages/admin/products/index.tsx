import { Head, Link, router } from '@inertiajs/react';
import { Eye, Package, Pencil, Plus, Search, Trash2, X } from 'lucide-react';
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
import products from '@/routes/admin/products';
import type { Paginated } from '@/types/admin';

interface ProductListItem {
    id: number;
    name: string;
    slug: string;
    type: string;
    amount: string;
    is_published: boolean;
    grants_founding_circle: boolean;
}

interface CatalogFilters {
    search: string;
    type: string;
    status: string;
    founding_circle: string;
    per_page: number;
}

const DEFAULT_PER_PAGE = 15;

function catalogQuery(
    filters: CatalogFilters,
): Record<string, string | number | undefined> {
    return {
        search: filters.search || undefined,
        type: filters.type || undefined,
        status: filters.status || undefined,
        founding_circle: filters.founding_circle || undefined,
        per_page:
            filters.per_page === DEFAULT_PER_PAGE
                ? undefined
                : filters.per_page,
    };
}

export default function ProductsIndex({
    products: paginated,
    filters,
    perPageOptions = [10, 15, 25, 50, 100],
}: {
    products: Paginated<ProductListItem>;
    filters: CatalogFilters;
    perPageOptions?: number[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState(filters.type || 'all');
    const [status, setStatus] = useState(filters.status || 'all');
    const [foundingCircle, setFoundingCircle] = useState(
        filters.founding_circle || 'all',
    );
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
                products.index(locale).url,
                catalogQuery({
                    search,
                    type: type === 'all' ? '' : type,
                    status: status === 'all' ? '' : status,
                    founding_circle:
                        foundingCircle === 'all' ? '' : foundingCircle,
                    per_page: perPage,
                }),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, type, status, foundingCircle, perPage, locale]);

    const hasActiveFilters =
        Boolean(search) ||
        type !== 'all' ||
        status !== 'all' ||
        foundingCircle !== 'all';

    const clearFilters = () => {
        setSearch('');
        setType('all');
        setStatus('all');
        setFoundingCircle('all');
    };

    return (
        <>
            <Head title={t('Product')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Product')}
                    description={t(
                        'Manage limited editions and simple stock products.',
                    )}
                    icon={Package}
                >
                    <Button asChild>
                        <Link href={products.create(locale)}>
                            <Plus className="h-4 w-4" />{' '}
                            {t('Add product')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <div className="relative sm:col-span-2 xl:col-span-2">
                        <Label htmlFor="catalog-search" className="sr-only">
                            {t('Search')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="catalog-search"
                            className="pl-9"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Search by name or slug...')}
                            aria-label={t('Search')}
                        />
                    </div>
                    <Select value={type} onValueChange={setType}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Type')}
                        >
                            <SelectValue placeholder={t('All types')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('All types')}
                            </SelectItem>
                            <SelectItem value="limited_edition">
                                {t('Limited edition')}
                            </SelectItem>
                            <SelectItem value="simple">
                                {t('Simple stock')}
                            </SelectItem>
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
                        value={foundingCircle}
                        onValueChange={setFoundingCircle}
                    >
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Founding Circle')}
                        >
                            <SelectValue placeholder={t('Founding Circle')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('All Founding Circle')}
                            </SelectItem>
                            <SelectItem value="yes">
                                {t('Grants access')}
                            </SelectItem>
                            <SelectItem value="no">
                                {t('No access')}
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
                            className="w-full xl:col-span-6 xl:w-auto xl:justify-self-start"
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
                                <TableHead>{t('Name')}</TableHead>
                                <TableHead>{t('Type')}</TableHead>
                                <TableHead>{t('Amount')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Founding Circle')}</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t('No products found.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((product) => (
                                    <TableRow key={product.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {product.name}
                                            </div>
                                            <div className="font-mono text-xs text-muted-foreground">
                                                {product.slug}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {product.type === 'limited_edition'
                                                ? t('Limited edition')
                                                : t('Simple stock')}
                                        </TableCell>
                                        <TableCell>
                                            € {product.amount}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {product.is_published
                                                    ? t('Published')
                                                    : t('Draft')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {product.grants_founding_circle
                                                ? t('Grants access')
                                                : t('No access')}
                                        </TableCell>
                                        <TableCell className="space-x-2 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={products.show({
                                                        locale,
                                                        product: product.id,
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
                                                    href={products.edit({
                                                        locale,
                                                        product: product.id,
                                                    })}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    {t('Edit')}
                                                </Link>
                                            </Button>
                                            <ConfirmDeleteDialog
                                                description={t(
                                                    'This product and unused edition pieces will be permanently deleted.',
                                                )}
                                                onConfirm={() =>
                                                    router.delete(
                                                        products.destroy({
                                                            locale,
                                                            product: product.id,
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

ProductsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Product', href: products.index(wayfinderLocale()) },
    ],
};
