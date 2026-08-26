import { Head, Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Search, Shirt, Trash2, X } from 'lucide-react';
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
import dressingItems from '@/routes/admin/dressing-items';
import type { Paginated } from '@/types/admin';

interface DressingItemRow {
    id: string;
    name: string;
    slug: string;
    category: string;
    status: string;
    sort_order: number;
    is_published: boolean;
    image_url: string | null;
}

interface DressingItemFilters {
    search: string;
    status: string;
    publication: string;
    per_page: number;
}

const DEFAULT_PER_PAGE = 15;

function dressingItemQuery(
    filters: DressingItemFilters,
): Record<string, string | number | undefined> {
    return {
        search: filters.search || undefined,
        status: filters.status || undefined,
        publication: filters.publication || undefined,
        per_page:
            filters.per_page === DEFAULT_PER_PAGE
                ? undefined
                : filters.per_page,
    };
}

export default function DressingItemsIndex({
    items: paginated,
    filters,
    perPageOptions = [10, 15, 25, 50, 100],
}: {
    items: Paginated<DressingItemRow>;
    filters: DressingItemFilters;
    perPageOptions?: number[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [publication, setPublication] = useState(
        filters.publication || 'all',
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
                dressingItems.index(locale).url,
                dressingItemQuery({
                    search,
                    status: status === 'all' ? '' : status,
                    publication: publication === 'all' ? '' : publication,
                    per_page: perPage,
                }),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, status, publication, perPage, locale]);

    const hasActiveFilters =
        Boolean(search) || status !== 'all' || publication !== 'all';

    const clearFilters = () => {
        setSearch('');
        setStatus('all');
        setPublication('all');
    };

    const statusLabel = (value: string) =>
        value === 'available' ? t('Beschikbaar') : t('Binnenkort');

    return (
        <>
            <Head title={t('Kleedkamer')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Kleedkamer')}
                    description={t(
                        'Beheer de items van de publieke Kleedkamer-catalogus.',
                    )}
                    icon={Shirt}
                >
                    <Button asChild>
                        <Link href={dressingItems.create(locale)}>
                            <Plus className="h-4 w-4" /> {t('Item toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    <div className="relative sm:col-span-2">
                        <Label
                            htmlFor="dressing-item-search"
                            className="sr-only"
                        >
                            {t('Zoeken')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="dressing-item-search"
                            className="pl-9"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Zoek op naam of slug…')}
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
                            <SelectItem value="coming_soon">
                                {t('Binnenkort')}
                            </SelectItem>
                            <SelectItem value="available">
                                {t('Beschikbaar')}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select
                        value={publication}
                        onValueChange={setPublication}
                    >
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Publicatie')}
                        >
                            <SelectValue
                                placeholder={t('Alle publicaties')}
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('Alle publicaties')}
                            </SelectItem>
                            <SelectItem value="published">
                                {t('Gepubliceerd')}
                            </SelectItem>
                            <SelectItem value="draft">
                                {t('Concept')}
                            </SelectItem>
                        </SelectContent>
                    </Select>
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
                                <SelectItem
                                    key={option}
                                    value={String(option)}
                                >
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
                            onClick={clearFilters}
                            className="w-full sm:col-span-2 lg:col-span-4 xl:col-span-5 xl:w-auto xl:justify-self-start"
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
                                <TableHead className="w-16" />
                                <TableHead>{t('Naam')}</TableHead>
                                <TableHead>{t('Categorie')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead>{t('Publicatie')}</TableHead>
                                <TableHead>{t('Volgorde')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {hasActiveFilters
                                            ? t('Geen items gevonden.')
                                            : t('Nog geen items toegevoegd.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell>
                                            {item.image_url ? (
                                                <img
                                                    src={item.image_url}
                                                    alt={item.name}
                                                    className="size-10 rounded-md object-cover"
                                                />
                                            ) : (
                                                <div className="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                                    <Shirt className="h-4 w-4" />
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {item.name}
                                        </TableCell>
                                        <TableCell>{item.category}</TableCell>
                                        <TableCell>
                                            {statusLabel(item.status)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {item.is_published
                                                    ? t('Gepubliceerd')
                                                    : t('Concept')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {item.sort_order}
                                        </TableCell>
                                        <TableCell className="space-x-2 text-right">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={dressingItems.show({
                                                        locale,
                                                        dressingItem: Number(
                                                            item.id,
                                                        ),
                                                    })}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                    {t('Bekijken')}
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={dressingItems.edit({
                                                        locale,
                                                        dressingItem: Number(
                                                            item.id,
                                                        ),
                                                    })}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    {t('Bewerken')}
                                                </Link>
                                            </Button>
                                            <ConfirmDeleteDialog
                                                description={t(
                                                    'Dit item wordt permanent verwijderd.',
                                                )}
                                                onConfirm={() =>
                                                    router.delete(
                                                        dressingItems.destroy({
                                                            locale,
                                                            dressingItem:
                                                                Number(
                                                                    item.id,
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
        </>
    );
}

DressingItemsIndex.layout = {
    title: 'Kleedkamer',
};
