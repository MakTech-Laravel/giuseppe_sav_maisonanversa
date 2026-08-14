import { Head, Link, router } from '@inertiajs/react';
import { Package, Pencil, Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { DataPagination } from '@/components/admin/data-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
}

export default function ProductsIndex({
    products: paginated,
    filters,
}: {
    products: Paginated<ProductListItem>;
    filters: { search: string };
}) {
    const { t } = useTranslation();
    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                products.index(wayfinderLocale()).url,
                { search: search || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search]);

    return (
        <>
            <Head title={t('Catalogus')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Catalogus')}
                    description={t(
                        'Beheer limited editions en eenvoudige voorraadproducten.',
                    )}
                    icon={Package}
                >
                    <Button asChild>
                        <Link href={products.create(wayfinderLocale())}>
                            <Plus className="h-4 w-4" /> {t('Product toevoegen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        className="pl-9"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={t('Zoeken')}
                    />
                </div>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Naam')}</TableHead>
                                <TableHead>{t('Type')}</TableHead>
                                <TableHead>{t('Bedrag')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginated.data.map((product) => (
                                <TableRow key={product.id}>
                                    <TableCell className="font-medium">
                                        {product.name}
                                    </TableCell>
                                    <TableCell>
                                        {product.type === 'limited_edition'
                                            ? t('Gelimiteerde editie')
                                            : t('Eenvoudige voorraad')}
                                    </TableCell>
                                    <TableCell>€ {product.amount}</TableCell>
                                    <TableCell>
                                        {product.is_published
                                            ? t('Gepubliceerd')
                                            : t('Concept')}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button variant="outline" size="sm" asChild>
                                            <Link
                                                href={products.edit({
                                                    locale: wayfinderLocale(),
                                                    product: product.id,
                                                })}
                                            >
                                                <Pencil className="h-4 w-4" />
                                                {t('Bewerken')}
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
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
        { title: 'Catalogus', href: products.index(wayfinderLocale()) },
    ],
};
