import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ReceiptText } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import orders from '@/routes/admin/orders';

interface TimelineItem {
    label: string;
    at: string;
    done: boolean;
}

interface Order {
    id: string;
    customer: string;
    label: string;
    date: string;
    amount: string;
    status: string;
    status_key: string;
    summary: string;
    items: { name: string; qty: number; price: string }[];
    billing?: { name: string; email: string; address: string };
    timeline?: TimelineItem[];
}

export default function ShowOrder({ order }: { order: Order }) {
    const { t } = useTranslation();
    const canShip = ['paid', 'incomplete'].includes(order.status_key);
    const canDeliver = ['paid', 'shipped'].includes(order.status_key);
    const canRefund = !['refunded', 'canceled', 'failed'].includes(
        order.status_key,
    );

    const updateStatus = (status: 'shipped' | 'delivered' | 'refunded') => {
        router.patch(
            orders.update({
                locale: wayfinderLocale(),
                order: order.id,
            }).url,
            { status },
            { preserveScroll: true },
        );
    };

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
                <div className="flex flex-wrap gap-2">
                    {canShip && (
                        <Button
                            type="button"
                            onClick={() => updateStatus('shipped')}
                        >
                            {t('Markeer als verzonden')}
                        </Button>
                    )}
                    {canDeliver && (
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => updateStatus('delivered')}
                        >
                            {t('Markeer als geleverd')}
                        </Button>
                    )}
                    {canRefund && (
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => updateStatus('refunded')}
                        >
                            {t('Markeer als terugbetaald')}
                        </Button>
                    )}
                </div>
                <div className="grid max-w-4xl gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <div className="rounded-xl border bg-card p-6 shadow-sm">
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
                        {order.timeline && order.timeline.length > 0 && (
                            <div className="rounded-xl border bg-card p-6 shadow-sm">
                                <h2 className="mb-4 text-sm font-semibold">
                                    {t('Tijdlijn')}
                                </h2>
                                <ol className="space-y-3 text-sm">
                                    {order.timeline.map((step) => (
                                        <li
                                            key={step.label}
                                            className="flex justify-between gap-4"
                                        >
                                            <span
                                                className={
                                                    step.done
                                                        ? 'font-medium'
                                                        : 'text-muted-foreground'
                                                }
                                            >
                                                {step.label}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {step.at || '—'}
                                            </span>
                                        </li>
                                    ))}
                                </ol>
                            </div>
                        )}
                    </div>
                    <dl className="space-y-4 rounded-xl border bg-card p-6 text-sm shadow-sm">
                        <Detail
                            label={t('Klant')}
                            value={order.billing?.name ?? order.customer}
                        />
                        {order.billing?.email && (
                            <Detail
                                label={t('E-mail')}
                                value={order.billing.email}
                            />
                        )}
                        {order.billing?.address && (
                            <Detail
                                label={t('Telefoon')}
                                value={order.billing.address}
                            />
                        )}
                        <Detail label={t('Datum')} value={order.date} />
                        <Detail label={t('Totaal')} value={order.amount} />
                        <div>
                            <dt className="text-muted-foreground">
                                {t('Status')}
                            </dt>
                            <dd className="mt-1">
                                <Badge variant="secondary">{order.status}</Badge>
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
