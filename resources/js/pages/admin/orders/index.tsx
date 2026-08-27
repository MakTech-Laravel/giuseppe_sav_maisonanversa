import { Head, Link } from '@inertiajs/react';
import { Eye, ShoppingBag } from 'lucide-react';
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
import ordersRoutes from '@/routes/admin/orders';

interface Order {
    id: string;
    customer: string;
    label: string;
    date: string;
    amount: string;
    status: string;
    status_key: string;
}

export default function OrdersIndex({ orders }: { orders: Order[] }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Bestellingen')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Bestellingen')}
                    description={t(
                        'Bekijk reserveringen, aanbetalingen en aankopen.',
                    )}
                    icon={ShoppingBag}
                />
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Bestelling')}</TableHead>
                                <TableHead>{t('Klant')}</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('Datum')}
                                </TableHead>
                                <TableHead>{t('Bedrag')}</TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {orders.map((order) => (
                                <TableRow key={order.id}>
                                    <TableCell>
                                        <span className="font-medium">
                                            {order.id}
                                        </span>
                                        <p className="text-xs text-muted-foreground">
                                            {order.label}
                                        </p>
                                    </TableCell>
                                    <TableCell>{order.customer}</TableCell>
                                    <TableCell className="hidden md:table-cell">
                                        {order.date}
                                    </TableCell>
                                    <TableCell>{order.amount}</TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {order.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                        >
                                            <Link
                                                href={ordersRoutes.show({
                                                    locale: wayfinderLocale(),
                                                    order: Number(order.id),
                                                })}
                                                title={t('Bestelling bekijken')}
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </Button>
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

OrdersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Bestellingen', href: ordersRoutes.index(wayfinderLocale()) },
    ],
};
