import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
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
    payment_method?: string | null;
    resolved_at?: string | null;
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
    const expectedAmount = orderReturn.expected_refund_amount ?? '';

    return (
        <>
            <Head title={orderReturn.number} />
            <AdminPageHeader
                title={orderReturn.number}
                description={`${orderReturn.order_reference ?? ''} · ${orderReturn.customer ?? ''}`}
            />

            <AdminResourceShell
                aside={
                    <div className="space-y-4">
                        {(orderReturn.can_receive ||
                            orderReturn.can_refund ||
                            orderReturn.can_decline ||
                            orderReturn.can_cancel) && (
                            <AdminPanel
                                title={t('Acties')}
                                description={t(
                                    'Verwerk deze retouraanvraag.',
                                )}
                            >
                                <div className="flex flex-col gap-3">
                                    {orderReturn.can_receive ? (
                                        <Form
                                            action={`/${locale}/admin/returns/${orderReturn.id}/receive`}
                                            method="post"
                                        >
                                            <Button
                                                type="submit"
                                                className="w-full"
                                            >
                                                {t('Markeer als ontvangen')}
                                            </Button>
                                        </Form>
                                    ) : null}

                                    {orderReturn.can_refund ? (
                                        <ConfirmDeleteDialog
                                            title={t(
                                                'Terugbetaling bevestigen',
                                            )}
                                            description={t(
                                                '€{{amount}} terugbetalen aan de klant?',
                                                {
                                                    amount:
                                                        expectedAmount || '—',
                                                },
                                            )}
                                            confirmLabel={t(
                                                'Volledig terugbetalen',
                                            )}
                                            onConfirm={() =>
                                                router.post(
                                                    `/${locale}/admin/returns/${orderReturn.id}/refund`,
                                                )
                                            }
                                        >
                                            <Button
                                                type="button"
                                                className="w-full"
                                            >
                                                {t('Volledig terugbetalen')}
                                            </Button>
                                        </ConfirmDeleteDialog>
                                    ) : null}

                                    {orderReturn.can_cancel ? (
                                        <Form
                                            action={`/${locale}/admin/returns/${orderReturn.id}/cancel`}
                                            method="post"
                                        >
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                className="w-full"
                                            >
                                                {t('Annuleren')}
                                            </Button>
                                        </Form>
                                    ) : null}
                                </div>
                            </AdminPanel>
                        )}

                        {orderReturn.can_refund ? (
                            <AdminPanel title={t('Gedeeltelijk terugbetalen')}>
                                <Form
                                    action={`/${locale}/admin/returns/${orderReturn.id}/partial-refund`}
                                    method="post"
                                    className="space-y-3"
                                >
                                    <div className="space-y-1.5">
                                        <Label htmlFor="refund_amount">
                                            {t('Gedeeltelijk bedrag')}
                                        </Label>
                                        <Input
                                            id="refund_amount"
                                            name="refund_amount"
                                            type="number"
                                            min="0.01"
                                            max={expectedAmount || undefined}
                                            step="0.01"
                                            required
                                            defaultValue={expectedAmount}
                                        />
                                        {expectedAmount ? (
                                            <p className="text-xs text-muted-foreground">
                                                {t('Maximum')}: €
                                                {expectedAmount}
                                            </p>
                                        ) : null}
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label htmlFor="partial_reason">
                                            {t('Reden')}
                                        </Label>
                                        <Textarea
                                            id="partial_reason"
                                            name="refund_reason"
                                            required
                                            rows={3}
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        className="w-full"
                                    >
                                        {t('Gedeeltelijk terugbetalen')}
                                    </Button>
                                </Form>
                            </AdminPanel>
                        ) : null}

                        {orderReturn.can_decline ? (
                            <AdminPanel title={t('Afwijzen')}>
                                <Form
                                    action={`/${locale}/admin/returns/${orderReturn.id}/decline`}
                                    method="post"
                                    className="space-y-3"
                                >
                                    <div className="space-y-1.5">
                                        <Label htmlFor="decline_reason">
                                            {t('Reden')}
                                        </Label>
                                        <Textarea
                                            id="decline_reason"
                                            name="refund_reason"
                                            required
                                            rows={3}
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        variant="destructive"
                                        className="w-full"
                                    >
                                        {t('Afwijzen')}
                                    </Button>
                                </Form>
                            </AdminPanel>
                        ) : null}

                        <AdminPanel title={t('Tracking')}>
                            <Form
                                action={`/${locale}/admin/returns/${orderReturn.id}/tracking`}
                                method="post"
                                className="space-y-3"
                            >
                                <div className="space-y-1.5">
                                    <Label htmlFor="carrier">
                                        {t('Vervoerder')}
                                    </Label>
                                    <Input
                                        id="carrier"
                                        name="carrier"
                                        defaultValue={
                                            orderReturn.carrier ?? ''
                                        }
                                        required
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="tracking_number">
                                        {t('Trackingnummer')}
                                    </Label>
                                    <Input
                                        id="tracking_number"
                                        name="tracking_number"
                                        defaultValue={
                                            orderReturn.tracking_number ?? ''
                                        }
                                        required
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="w-full"
                                >
                                    {t('Tracking opslaan')}
                                </Button>
                            </Form>
                        </AdminPanel>

                        <AdminPanel title={t('Termijn verlengen')}>
                            <Form
                                action={`/${locale}/admin/returns/${orderReturn.id}/extend`}
                                method="post"
                                className="space-y-3"
                            >
                                <div className="space-y-1.5">
                                    <Label htmlFor="return_deadline_at">
                                        {t('Nieuwe deadline')}
                                    </Label>
                                    <Input
                                        id="return_deadline_at"
                                        name="return_deadline_at"
                                        type="date"
                                        required
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="w-full"
                                >
                                    {t('Opslaan')}
                                </Button>
                            </Form>
                        </AdminPanel>

                        <AdminPanel title={t('Notitie')}>
                            <Form
                                action={`/${locale}/admin/returns/${orderReturn.id}/note`}
                                method="post"
                                className="space-y-3"
                            >
                                <Textarea name="note" required rows={3} />
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="w-full"
                                >
                                    {t('Notitie toevoegen')}
                                </Button>
                            </Form>
                        </AdminPanel>
                    </div>
                }
            >
                <div className="space-y-6">
                    <AdminPanel title={t('Details')}>
                        <dl className="space-y-2.5 text-sm">
                            <div className="flex gap-3">
                                <dt className="w-28 shrink-0 text-muted-foreground">
                                    {t('Status')}
                                </dt>
                                <dd className="font-medium">
                                    {t(orderReturn.status_label)}
                                </dd>
                            </div>
                            <div className="flex gap-3">
                                <dt className="w-28 shrink-0 text-muted-foreground">
                                    {t('Reden')}
                                </dt>
                                <dd className="font-medium">
                                    {orderReturn.reason
                                        ? t(orderReturn.reason)
                                        : '—'}
                                </dd>
                            </div>
                            <div className="flex gap-3">
                                <dt className="w-28 shrink-0 text-muted-foreground">
                                    {t('Vervoerder')}
                                </dt>
                                <dd className="font-medium">
                                    {orderReturn.carrier || '—'}
                                </dd>
                            </div>
                            <div className="flex gap-3">
                                <dt className="w-28 shrink-0 text-muted-foreground">
                                    {t('Trackingnummer')}
                                </dt>
                                <dd className="font-medium break-all">
                                    {orderReturn.tracking_number || '—'}
                                </dd>
                            </div>
                            <div className="flex gap-3">
                                <dt className="w-28 shrink-0 text-muted-foreground">
                                    {t('Verwacht')}
                                </dt>
                                <dd className="font-medium">
                                    {expectedAmount
                                        ? `€${expectedAmount}`
                                        : '—'}
                                </dd>
                            </div>
                            {orderReturn.refund_amount ? (
                                <div className="flex gap-3">
                                    <dt className="w-28 shrink-0 text-muted-foreground">
                                        {t('Terugbetaald')}
                                    </dt>
                                    <dd className="font-medium">
                                        €{orderReturn.refund_amount}
                                    </dd>
                                </div>
                            ) : null}
                        </dl>

                        {orderReturn.comment ? (
                            <p className="mt-4 text-sm text-muted-foreground">
                                {orderReturn.comment}
                            </p>
                        ) : null}

                        {orderReturn.maison_pays_return_shipping ? (
                            <p className="mt-4 rounded-md border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm font-medium text-amber-800 dark:text-amber-200">
                                {t(
                                    'Beschadigd — retourzending op onze kosten',
                                )}
                            </p>
                        ) : null}

                        {orderReturn.stripe_refund_id ? (
                            <p className="mt-3 text-sm text-muted-foreground">
                                Stripe: {orderReturn.stripe_refund_id}
                            </p>
                        ) : null}

                        <div className="mt-4">
                            <Link
                                href={`/${locale}/admin/orders/${orderReturn.order_id}`}
                                className="text-sm font-medium underline underline-offset-4"
                            >
                                {t('Bekijk bestelling')}
                            </Link>
                        </div>
                    </AdminPanel>

                    {orderReturn.photos.length > 0 ? (
                        <AdminPanel title={t("Foto's")}>
                            <div className="flex flex-wrap gap-3">
                                {orderReturn.photos.map((photo) => (
                                    <a
                                        key={photo.id}
                                        href={photo.url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="overflow-hidden rounded-md border"
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
                        {orderReturn.events.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('Nog geen gebeurtenissen.')}
                            </p>
                        ) : (
                            <ul className="space-y-4">
                                {orderReturn.events.map((event) => (
                                    <li
                                        key={event.id}
                                        className="border-b border-border/60 pb-3 last:border-0 last:pb-0"
                                    >
                                        <p className="text-sm font-medium">
                                            {t(event.to)}
                                        </p>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {event.user ?? '—'} ·{' '}
                                            {event.at ?? '—'}
                                        </p>
                                        {event.note ? (
                                            <p className="mt-2 text-sm text-muted-foreground">
                                                {t(event.note)}
                                            </p>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </AdminPanel>
                </div>
            </AdminResourceShell>
        </>
    );
}
