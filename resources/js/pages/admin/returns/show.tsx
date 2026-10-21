import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type ReturnDetail = {
    id: number;
    number: string;
    status_label: string;
    customer: string | null;
    order_reference: string | null;
    order_id: number;
    reason: string | null;
    comment: string | null;
    expected_refund_amount: string | null;
    refund_amount: string | null;
    refund_reason: string | null;
    stripe_refund_id: string | null;
    carrier: string | null;
    tracking_number: string | null;
    maison_pays_return_shipping: boolean;
    can_receive: boolean;
    can_refund: boolean;
    can_decline: boolean;
    can_cancel: boolean;
    photos: { id: number; url: string }[];
    events: {
        id: number;
        from: string | null;
        to: string;
        note: string | null;
        user: string | null;
        at: string | null;
    }[];
};

export default function AdminReturnShow({
    return: orderReturn,
}: {
    return: ReturnDetail;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    return (
        <>
            <Head title={orderReturn.number} />
            <AdminPageHeader
                title={orderReturn.number}
                description={`${orderReturn.order_reference ?? ''} · ${orderReturn.customer ?? ''}`}
            />

            <AdminResourceShell
                main={
                    <div className="space-y-6">
                        <AdminPanel title={t('Details')}>
                            <p>
                                {t('Status')}: {t(orderReturn.status_label)}
                            </p>
                            <p>
                                {t('Reden')}: {orderReturn.reason ?? '—'}
                            </p>
                            <p>{orderReturn.comment}</p>
                            <p>
                                {t('Verwacht')}: €
                                {orderReturn.expected_refund_amount}
                            </p>
                            {orderReturn.maison_pays_return_shipping ? (
                                <p className="font-medium text-amber-700">
                                    {t(
                                        'Beschadigd — retourzending op onze kosten',
                                    )}
                                </p>
                            ) : null}
                            {orderReturn.stripe_refund_id ? (
                                <p>
                                    Stripe: {orderReturn.stripe_refund_id} (€
                                    {orderReturn.refund_amount})
                                </p>
                            ) : null}
                            <Link
                                href={`/${locale}/admin/orders/${orderReturn.order_id}`}
                                className="text-sm underline"
                            >
                                {t('Bekijk bestelling')}
                            </Link>
                        </AdminPanel>

                        {orderReturn.photos.length > 0 ? (
                            <AdminPanel title={t('Foto\'s')}>
                                <div className="flex flex-wrap gap-3">
                                    {orderReturn.photos.map((photo) => (
                                        <a
                                            key={photo.id}
                                            href={photo.url}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            <img
                                                src={photo.url}
                                                alt=""
                                                className="h-24 w-24 object-cover"
                                            />
                                        </a>
                                    ))}
                                </div>
                            </AdminPanel>
                        ) : null}

                        <AdminPanel title={t('Log')}>
                            <ul className="space-y-3">
                                {orderReturn.events.map((event) => (
                                    <li key={event.id} className="text-sm">
                                        <span className="font-medium">
                                            {event.to}
                                        </span>{' '}
                                        · {event.user ?? '—'} · {event.at}
                                        {event.note ? (
                                            <p className="text-muted-foreground">
                                                {event.note}
                                            </p>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        </AdminPanel>
                    </div>
                }
                aside={
                    <div className="space-y-4">
                        {orderReturn.can_receive ? (
                            <Form
                                action={`/${locale}/admin/returns/${orderReturn.id}/receive`}
                                method="post"
                            >
                                <Button type="submit" className="w-full">
                                    {t('Markeer als ontvangen')}
                                </Button>
                            </Form>
                        ) : null}

                        {orderReturn.can_refund ? (
                            <>
                                <Form
                                    action={`/${locale}/admin/returns/${orderReturn.id}/refund`}
                                    method="post"
                                >
                                    <Button type="submit" className="w-full">
                                        {t('Volledig terugbetalen')}
                                    </Button>
                                </Form>

                                <Form
                                    action={`/${locale}/admin/returns/${orderReturn.id}/partial-refund`}
                                    method="post"
                                    className="space-y-2 rounded border p-3"
                                >
                                    <Label>{t('Gedeeltelijk bedrag')}</Label>
                                    <Input
                                        name="refund_amount"
                                        type="number"
                                        step="0.01"
                                        required
                                        defaultValue={
                                            orderReturn.expected_refund_amount ??
                                            ''
                                        }
                                    />
                                    <Label>{t('Reden')}</Label>
                                    <Textarea name="refund_reason" required />
                                    <Button type="submit" variant="outline">
                                        {t('Gedeeltelijk terugbetalen')}
                                    </Button>
                                </Form>
                            </>
                        ) : null}

                        {orderReturn.can_decline ? (
                            <Form
                                action={`/${locale}/admin/returns/${orderReturn.id}/decline`}
                                method="post"
                                className="space-y-2 rounded border p-3"
                            >
                                <Label>{t('Afwijzen')}</Label>
                                <Textarea name="refund_reason" required />
                                <Button type="submit" variant="destructive">
                                    {t('Afwijzen')}
                                </Button>
                            </Form>
                        ) : null}

                        <Form
                            action={`/${locale}/admin/returns/${orderReturn.id}/tracking`}
                            method="post"
                            className="space-y-2 rounded border p-3"
                        >
                            <Label>{t('Tracking')}</Label>
                            <Input
                                name="carrier"
                                defaultValue={orderReturn.carrier ?? ''}
                                required
                            />
                            <Input
                                name="tracking_number"
                                defaultValue={orderReturn.tracking_number ?? ''}
                                required
                            />
                            <Button type="submit" variant="outline">
                                {t('Tracking opslaan')}
                            </Button>
                        </Form>

                        <Form
                            action={`/${locale}/admin/returns/${orderReturn.id}/extend`}
                            method="post"
                            className="space-y-2 rounded border p-3"
                        >
                            <Label>{t('Termijn verlengen')}</Label>
                            <Input name="return_deadline_at" type="date" required />
                            <Button type="submit" variant="outline">
                                {t('Opslaan')}
                            </Button>
                        </Form>

                        <Form
                            action={`/${locale}/admin/returns/${orderReturn.id}/note`}
                            method="post"
                            className="space-y-2 rounded border p-3"
                        >
                            <Label>{t('Notitie')}</Label>
                            <Textarea name="note" required />
                            <Button type="submit" variant="outline">
                                {t('Notitie toevoegen')}
                            </Button>
                        </Form>

                        {orderReturn.can_cancel ? (
                            <Form
                                action={`/${locale}/admin/returns/${orderReturn.id}/cancel`}
                                method="post"
                            >
                                <Button type="submit" variant="ghost">
                                    {t('Annuleren')}
                                </Button>
                            </Form>
                        ) : null}
                    </div>
                }
            />
        </>
    );
}
