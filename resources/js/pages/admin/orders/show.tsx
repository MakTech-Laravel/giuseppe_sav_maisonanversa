import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ReceiptText, TriangleAlert } from 'lucide-react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import orders from '@/routes/admin/orders';

interface Order {
    id: string;
    customer: string;
    label: string;
    date: string;
    amount: string;
    status: string;
    summary: string;
    items: { name: string; qty: number; price: string }[];
}

export default function ShowOrder({
    order,
    commerceConnected,
}: {
    order: Order;
    commerceConnected: boolean;
}) {
    return (
        <>
            <Head title={order.id} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={order.id}
                    description={order.summary}
                    icon={ReceiptText}
                >
                    <Button variant="outline" asChild>
                        <Link href={orders.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> Back to orders
                        </Link>
                    </Button>
                </AdminPageHeader>
                {!commerceConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>Demo order</AlertTitle>
                        <AlertDescription>
                            Live commerce details will appear here after a
                            provider is connected.
                        </AlertDescription>
                    </Alert>
                )}
                <div className="grid max-w-4xl gap-6 lg:grid-cols-3">
                    <div className="rounded-xl border bg-card p-6 shadow-sm lg:col-span-2">
                        <h2 className="mb-4 text-sm font-semibold">
                            Order items
                        </h2>
                        <div className="divide-y">
                            {order.items.map((item) => (
                                <div
                                    key={item.name}
                                    className="flex justify-between gap-4 py-3 text-sm"
                                >
                                    <span>
                                        {item.name} × {item.qty}
                                    </span>
                                    <span className="font-medium">
                                        {item.price}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                    <dl className="space-y-4 rounded-xl border bg-card p-6 text-sm shadow-sm">
                        <Detail label="Customer" value={order.customer} />
                        <Detail label="Date" value={order.date} />
                        <Detail label="Total" value={order.amount} />
                        <div>
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="mt-1">
                                <Badge variant="secondary">
                                    {order.status}
                                </Badge>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-medium">{value}</dd>
        </div>
    );
}

ShowOrder.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Orders', href: orders.index(wayfinderLocale()) },
        { title: 'Details', href: orders.index(wayfinderLocale()) },
    ],
};
