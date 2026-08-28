import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ReceiptText } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import customers from '@/routes/admin/customers';
import orders from '@/routes/admin/orders';

interface TimelineItem {
    label: string;
    at: string;
    done: boolean;
    status_key?: string;
}

interface OrderEvent {
    id: string;
    status: string;
    status_key: string;
    message: string | null;
    at: string;
}

interface Order {
    id: string;
    reference?: string;
    customer: string;
    user_id?: number | null;
    label: string;
    date: string;
    amount: string;
    status: string;
    status_key: string;
    summary: string;
    items: { name: string; qty: number; price: string }[];
    billing?: { name: string; email: string; phone?: string; address?: string };
    shipping?: {
        line1: string;
        line2?: string | null;
        city: string;
        postal_code: string;
        country: string;
    };
    payment?: {
        id: string;
        status: string;
        status_key: string;
        provider: string;
        stripe_checkout_session_id: string | null;
        stripe_payment_intent_id: string | null;
        amount: string;
    } | null;
    timeline?: TimelineItem[];
    events?: OrderEvent[];
}

export default function ShowOrder({ order }: { order: Order }) {
    const { t } = useTranslation();
    const form = useForm({
        status: 'processing' as
            | 'processing'
            | 'shipped'
            | 'delivered'
            | 'refunded'
            | 'canceled',
        message: '',
    });

    const canProcess = order.status_key === 'paid';
    const canShip = ['paid', 'processing'].includes(order.status_key);
    const canDeliver = order.status_key === 'shipped';
    const canCancel = order.status_key === 'incomplete';
    const canRefund = !['refunded', 'canceled', 'failed', 'incomplete'].includes(
        order.status_key,
    );

    const submitStatus = (
        status:
            | 'processing'
            | 'shipped'
            | 'delivered'
            | 'refunded'
            | 'canceled',
    ) => {
        router.patch(
            orders.update({
                locale: wayfinderLocale(),
                order: Number(order.id),
            }).url,
            {
                status,
                message: form.data.message,
            },
            {
                preserveScroll: true,
                onError: (errors) => {
                    form.setError('message', errors.message ?? '');
                    form.setError('status', errors.status ?? '');
                },
            },
        );
    };

    return (
        <>
            <Head title={order.reference ?? order.id} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={order.reference ?? order.id}
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

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <h2 className="mb-3 text-sm font-semibold">
                        {t('Status bijwerken')}
                    </h2>
                    <div className="mb-3 space-y-2">
                        <Label htmlFor="order-message">
                            {t('Bericht voor de koper')}
                        </Label>
                        <Textarea
                            id="order-message"
                            value={form.data.message}
                            onChange={(event) =>
                                form.setData('message', event.target.value)
                            }
                            rows={3}
                            placeholder={t(
                                'Korte update die de koper per e-mail en in het dashboard ziet…',
                            )}
                        />
                        <InputError message={form.errors.message} />
                        <InputError message={form.errors.status} />
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canProcess && (
                            <Button
                                type="button"
                                disabled={form.processing}
                                onClick={() => submitStatus('processing')}
                            >
                                {t('Markeer als in verwerking')}
                            </Button>
                        )}
                        {canShip && (
                            <Button
                                type="button"
                                disabled={form.processing}
                                onClick={() => submitStatus('shipped')}
                            >
                                {t('Markeer als verzonden')}
                            </Button>
                        )}
                        {canDeliver && (
                            <Button
                                type="button"
                                variant="secondary"
                                disabled={form.processing}
                                onClick={() => submitStatus('delivered')}
                            >
                                {t('Markeer als geleverd')}
                            </Button>
                        )}
                        {canCancel && (
                            <Button
                                type="button"
                                variant="destructive"
                                disabled={form.processing}
                                onClick={() => submitStatus('canceled')}
                            >
                                {t('Markeer als geannuleerd')}
                            </Button>
                        )}
                        {canRefund && (
                            <Button
                                type="button"
                                variant="destructive"
                                disabled={form.processing}
                                onClick={() => submitStatus('refunded')}
                            >
                                {t('Markeer als terugbetaald')}
                            </Button>
                        )}
                    </div>
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
                                <ol className="space-y-3">
                                    {order.timeline.map((item) => (
                                        <li
                                            key={item.label}
                                            className="flex items-start justify-between gap-3 text-sm"
                                        >
                                            <span
                                                className={
                                                    item.done
                                                        ? 'font-medium'
                                                        : 'text-muted-foreground'
                                                }
                                            >
                                                {item.label}
                                            </span>
                                            <span className="text-muted-foreground">
                                                {item.at || '—'}
                                            </span>
                                        </li>
                                    ))}
                                </ol>
                            </div>
                        )}
                        {order.events && order.events.length > 0 && (
                            <div className="rounded-xl border bg-card p-6 shadow-sm">
                                <h2 className="mb-4 text-sm font-semibold">
                                    {t('Berichten')}
                                </h2>
                                <ul className="space-y-4">
                                    {order.events.map((event) => (
                                        <li
                                            key={event.id}
                                            className="border-b pb-3 last:border-0 last:pb-0"
                                        >
                                            <div className="mb-1 flex items-center justify-between gap-2">
                                                <Badge variant="outline">
                                                    {event.status}
                                                </Badge>
                                                <span className="text-xs text-muted-foreground">
                                                    {event.at}
                                                </span>
                                            </div>
                                            <p className="text-sm">
                                                {event.message || '—'}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                    <div className="space-y-6">
                        <div className="rounded-xl border bg-card p-6 shadow-sm">
                            <h2 className="mb-4 text-sm font-semibold">
                                {t('Status')}
                            </h2>
                            <Badge>{order.status}</Badge>
                            <p className="mt-3 text-sm text-muted-foreground">
                                {order.date} · {order.amount}
                            </p>
                        </div>
                        {order.payment && (
                            <div className="rounded-xl border bg-card p-6 shadow-sm">
                                <h2 className="mb-4 text-sm font-semibold">
                                    {t('Betaling')}
                                </h2>
                                <dl className="space-y-2 text-sm">
                                    <div>
                                        <dt className="text-muted-foreground">
                                            {t('Betalingsstatus')}
                                        </dt>
                                        <dd>
                                            <Badge variant="secondary">
                                                {order.payment.status}
                                            </Badge>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            {t('Bedrag')}
                                        </dt>
                                        <dd>{order.payment.amount}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            {t('Provider')}
                                        </dt>
                                        <dd className="capitalize">
                                            {order.payment.provider}
                                        </dd>
                                    </div>
                                    {order.payment
                                        .stripe_checkout_session_id && (
                                        <div>
                                            <dt className="text-muted-foreground">
                                                {t('Stripe sessie')}
                                            </dt>
                                            <dd className="break-all font-mono text-xs">
                                                {
                                                    order.payment
                                                        .stripe_checkout_session_id
                                                }
                                            </dd>
                                        </div>
                                    )}
                                    {order.payment
                                        .stripe_payment_intent_id && (
                                        <div>
                                            <dt className="text-muted-foreground">
                                                {t('Stripe payment intent')}
                                            </dt>
                                            <dd className="break-all font-mono text-xs">
                                                {
                                                    order.payment
                                                        .stripe_payment_intent_id
                                                }
                                            </dd>
                                        </div>
                                    )}
                                </dl>
                            </div>
                        )}
                        {order.billing && (
                            <div className="rounded-xl border bg-card p-6 shadow-sm">
                                <h2 className="mb-4 text-sm font-semibold">
                                    {t('Koper')}
                                </h2>
                                <dl className="space-y-2 text-sm">
                                    <div>
                                        <dt className="text-muted-foreground">
                                            {t('Naam')}
                                        </dt>
                                        <dd>
                                            {order.user_id ? (
                                                <Link
                                                    href={customers.show({
                                                        locale: wayfinderLocale(),
                                                        user: order.user_id,
                                                    })}
                                                    className="font-medium underline-offset-2 hover:underline"
                                                >
                                                    {order.billing.name}
                                                </Link>
                                            ) : (
                                                order.billing.name
                                            )}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            {t('E-mail')}
                                        </dt>
                                        <dd>{order.billing.email}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground">
                                            {t('Telefoon')}
                                        </dt>
                                        <dd>
                                            {order.billing.phone ??
                                                order.billing.address ??
                                                '—'}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        )}
                        {order.shipping && (
                            <div className="rounded-xl border bg-card p-6 shadow-sm">
                                <h2 className="mb-4 text-sm font-semibold">
                                    {t('Verzendadres')}
                                </h2>
                                <p className="text-sm">
                                    {order.shipping.line1}
                                    {order.shipping.line2
                                        ? `, ${order.shipping.line2}`
                                        : ''}
                                    <br />
                                    {order.shipping.postal_code}{' '}
                                    {order.shipping.city}
                                    <br />
                                    {order.shipping.country}
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
