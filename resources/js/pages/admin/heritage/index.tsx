import { Head, useForm } from '@inertiajs/react';
import { Loader2, Package, TriangleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import heritageRoutes from '@/routes/admin/heritage';

interface HeritageProduct {
    id: number;
    name: string;
    amount: string;
    currency: string;
    stripe_price_id: string | null;
}

interface HeritageInventory {
    product_name: string;
    total: number;
    reserved: number;
    available: number;
    rows: {
        sku: string;
        label: string;
        status: string;
        status_key: string;
        notes: string;
    }[];
}

function translateInventoryStatus(
    status: string,
    t: (key: string) => string,
): string {
    const statusMap: Record<string, string> = {
        archive: 'Archief',
        available: 'Beschikbaar',
        reserved: 'Gereserveerd',
        allocated: 'Toegewezen',
    };

    return t(statusMap[status] ?? status);
}

export default function HeritageIndex({
    product,
    inventory,
    heritageConnected,
}: {
    product: HeritageProduct | null;
    inventory: HeritageInventory;
    heritageConnected: boolean;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Heritage-product')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Heritage-product')}
                    description={t(
                        'Bekijk Heritage No.001-voorraad en productstatus.',
                    )}
                    icon={Package}
                />
                {!heritageConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>
                            {t('Productbeheer is niet gekoppeld')}
                        </AlertTitle>
                        <AlertDescription>
                            {t(
                                'Voorraadtellingen komen uit configuratie; mutaties blijven demogegevens tot een voorraadtabel bestaat.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}
                {product && <HeritageProductForm product={product} />}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label={t('Product')}
                        value={inventory.product_name}
                        emphasize
                    />
                    <StatCard
                        label={t('Totaal')}
                        value={String(inventory.total)}
                    />
                    <StatCard
                        label={t('Gereserveerd')}
                        value={String(inventory.reserved)}
                    />
                    <StatCard
                        label={t('Beschikbaar')}
                        value={String(inventory.available)}
                    />
                </div>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('SKU')}</TableHead>
                                <TableHead>{t('Label')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Notities')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {inventory.rows.map((row) => (
                                <TableRow key={row.sku}>
                                    <TableCell className="font-medium">
                                        {row.sku}
                                    </TableCell>
                                    <TableCell>{row.label}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {translateInventoryStatus(
                                                row.status,
                                                t,
                                            )}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="hidden text-muted-foreground md:table-cell">
                                        {row.notes}
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

function HeritageProductForm({ product }: { product: HeritageProduct }) {
    const { t } = useTranslation();
    const form = useForm(
        heritageRoutes.update({
            locale: wayfinderLocale(),
            product: product.id,
        }),
        {
            name: product.name,
            amount: product.amount,
        },
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit();
    };

    return (
        <form
            onSubmit={submit}
            className="grid gap-4 rounded-xl border bg-card p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-3"
        >
            <div className="grid gap-2">
                <Label htmlFor="name">{t('Naam')}</Label>
                <Input
                    id="name"
                    value={form.data.name}
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                />
                <InputError message={form.errors.name} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="amount">{t('Bedrag')}</Label>
                <Input
                    id="amount"
                    name="amount"
                    type="text"
                    inputMode="decimal"
                    value={form.data.amount}
                    onChange={(event) =>
                        form.setData('amount', event.target.value)
                    }
                />
                <p className="text-xs text-muted-foreground">EUR</p>
                <InputError message={form.errors.amount} />
            </div>
            <div className="flex items-end">
                <Button type="submit" disabled={form.processing}>
                    {form.processing && (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    )}
                    {t('Wijzigingen opslaan')}
                </Button>
            </div>
        </form>
    );
}

function StatCard({
    label,
    value,
    emphasize = false,
}: {
    label: string;
    value: string;
    emphasize?: boolean;
}) {
    return (
        <div className="rounded-xl border bg-card p-5 shadow-sm">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p
                className={
                    emphasize
                        ? 'mt-2 text-sm font-medium leading-snug'
                        : 'mt-2 font-serif text-2xl font-medium'
                }
            >
                {value}
            </p>
        </div>
    );
}

HeritageIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Heritage-product',
            href: heritageRoutes.index(wayfinderLocale()),
        },
    ],
};
