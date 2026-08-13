import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ReceiptText, TriangleAlert } from 'lucide-react';
import { useTranslation } from 'react-i18next';
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

function translateOrderStatus(
    status: string,
    t: (key: string) => string,
): string {
    const statusMap: Record<string, string> = {
        Active: 'Actief',
        Reserved: 'Gereserveerd',
        Paid: 'Betaald',
        Cancelled: 'Geannuleerd',
        Pending: 'In behandeling',
    };

    return t(statusMap[status] ?? status);
}

export default function ShowOrder({
    order,
    commerceConnected,
}: {
    order: Order;
    commerceConnected: boolean;
}) {
    const { t } = useTranslation();

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
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar bestellingen')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                {!commerceConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>{t('Demobestelling')}</AlertTitle>
                        <AlertDescription>
                            {t(
                                'Live commercedetails verschijnen hier nadat een provider is gekoppeld.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}
                <div className="grid max-w-4xl gap-6 lg:grid-cols-3">
                    <div className="rounded-xl border bg-card p-6 shadow-sm lg:col-span-2">
                        <h2 className="mb-4 text-sm font-semibold">
                            {t('Bestelregels')}
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
                        <Detail
                            label={t('Klant')}
                            value={order.customer}
                        />
                        <Detail label={t('Datum')} value={order.date} />
                        <Detail label={t('Totaal')} value={order.amount} />
                        <div>
                            <dt className="text-muted-foreground">
                                {t('Status')}
                            </dt>
                            <dd className="mt-1">
                                <Badge variant="secondary">
                                    {translateOrderStatus(order.status, t)}
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
        { title: 'Bestellingen', href: orders.index(wayfinderLocale()) },
        { title: 'Gegevens', href: orders.index(wayfinderLocale()) },
    ],
};
