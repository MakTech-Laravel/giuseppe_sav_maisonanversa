import { Head, Link, router } from '@inertiajs/react';
import {
    BookOpen,
    Eye,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
} from 'lucide-react';
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
import journalRoutes from '@/routes/admin/journal';
import type { Paginated } from '@/types/admin';

interface ArticleRow {
    id: string;
    slug: string;
    title: string;
    category: string;
    author: string;
    sort_order: number;
    published_at: string | null;
    is_published: boolean;
    image_url: string | null;
}

interface JournalFilters {
    search: string;
    category: string;
    publication: string;
    per_page: number;
}

const DEFAULT_PER_PAGE = 15;

function journalQuery(
    filters: JournalFilters,
): Record<string, string | number | undefined> {
    return {
        search: filters.search || undefined,
        category: filters.category || undefined,
        publication: filters.publication || undefined,
        per_page:
            filters.per_page === DEFAULT_PER_PAGE
                ? undefined
                : filters.per_page,
    };
}

export default function JournalIndex({
    articles: paginated,
    filters,
    categories,
    perPageOptions = [10, 15, 25, 50, 100],
}: {
    articles: Paginated<ArticleRow>;
    filters: JournalFilters;
    categories: Array<{ value: string; label: string }>;
    perPageOptions?: number[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category || 'all');
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
                journalRoutes.index(locale).url,
                journalQuery({
                    search,
                    category: category === 'all' ? '' : category,
                    publication: publication === 'all' ? '' : publication,
                    per_page: perPage,
                }),
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, category, publication, perPage, locale]);

    const hasActiveFilters =
        Boolean(search) || category !== 'all' || publication !== 'all';

    return (
        <>
            <Head title={t('Journal')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Journal')}
                    description={t('Beheer redactionele journalartikelen.')}
                    icon={BookOpen}
                >
                    <Button asChild>
                        <Link href={journalRoutes.create(wayfinderLocale())}>
                            <Plus className="h-4 w-4" /> {t('Artikel toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    <div className="relative sm:col-span-2">
                        <Label htmlFor="journal-search" className="sr-only">
                            {t('Zoeken')}
                        </Label>
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="journal-search"
                            className="pl-9"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={t('Zoek op titel, slug of auteur…')}
                            aria-label={t('Zoeken')}
                        />
                    </div>
                    <Select value={category} onValueChange={setCategory}>
                        <SelectTrigger
                            className="w-full"
                            aria-label={t('Categorie')}
                        >
                            <SelectValue placeholder={t('Alle categorieen')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                {t('Alle categorieen')}
                            </SelectItem>
                            {categories.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
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
                            onClick={() => {
                                setSearch('');
                                setCategory('all');
                                setPublication('all');
                            }}
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
                                <TableHead>{t('Titel')}</TableHead>
                                <TableHead>{t('Categorie')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="hidden xl:table-cell">
                                    {t('Auteur')}
                                </TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
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
                                        {hasActiveFilters
                                            ? t('Geen artikelen gevonden.')
                                            : t('Nog geen artikelen toegevoegd.')}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.data.map((article) => (
                                    <TableRow key={article.id}>
                                        <TableCell>
                                            {article.image_url ? (
                                                <img
                                                    src={article.image_url}
                                                    alt={article.title}
                                                    className="size-10 rounded-md object-cover"
                                                />
                                            ) : (
                                                <div className="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                                    <BookOpen className="h-4 w-4" />
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            <div>{article.title}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {article.slug}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {article.category ? (
                                                <Badge variant="secondary">
                                                    {article.category}
                                                </Badge>
                                            ) : (
                                                '—'
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="secondary">
                                                {article.is_published
                                                    ? t('Gepubliceerd')
                                                    : t('Concept')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="hidden xl:table-cell">
                                            {article.author || '—'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={journalRoutes.show({
                                                            locale,
                                                            article: article.id,
                                                        })}
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
                                                        href={journalRoutes.edit({
                                                            locale,
                                                            article: article.id,
                                                        })}
                                                    >
                                                        <Pencil className="h-4 w-4" />
                                                    </Link>
                                                </Button>
                                                <ConfirmDeleteDialog
                                                    description={t(
                                                        'Dit journalartikel wordt permanent verwijderd.',
                                                    )}
                                                    onConfirm={() =>
                                                        router.delete(
                                                            journalRoutes.destroy({
                                                                locale,
                                                                article: article.id,
                                                            }).url,
                                                        )
                                                    }
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </ConfirmDeleteDialog>
                                            </div>
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

JournalIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Journal', href: journalRoutes.index(wayfinderLocale()) },
    ],
};
