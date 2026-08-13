import { Head, Link } from '@inertiajs/react';
import { Eye, ShoppingBag, TriangleAlert } from 'lucide-react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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

export default function OrdersIndex({
    orders,
    commerceConnected,
}: {
    orders: Order[];
    commerceConnected: boolean;
}) {
    return (
        <>
            <Head title="Orders" />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title="Orders"
                    description="Review reservations, deposits, and purchases."
                    icon={ShoppingBag}
                />
                {!commerceConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>Commerce is not connected</AlertTitle>
                        <AlertDescription>
                            These orders are demonstration data until the
                            commerce provider is configured.
                        </AlertDescription>
                    </Alert>
                )}
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>Order</TableHead>
                                <TableHead>Customer</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    Date
                                </TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
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
                                                    order: order.id,
                                                })}
                                                title="View order"
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
