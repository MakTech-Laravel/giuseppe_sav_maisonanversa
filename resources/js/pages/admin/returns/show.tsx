import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminDatePicker } from '@/components/admin/admin-date-picker';
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
    const [partialAmount, setPartialAmount] = useState(
        expectedAmount || '',
    );
    const [partialReason, setPartialReason] = useState('');
    const [editingTracking, setEditingTracking] = useState(
        !orderReturn.tracking_number,
    );
    const [deadline, setDeadline] = useState('');

    useEffect(() => {
        if (orderReturn.tracking_number) {
            setEditingTracking(false);
        }
    }, [orderReturn.tracking_number, orderReturn.carrier]);

    return (
        <>
            <Head title={orderReturn.number} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
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
                                title={t('Actions')}
                                description={t(
                                    'Process this return request.',
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
                                                {t('Mark as received')}
                                            </Button>
                                        </Form>
                                    ) : null}

                                    {orderReturn.can_refund ? (
                                        <ConfirmDeleteDialog
                                            title={t(
                                                'Confirm refund',
                                            )}
                                            description={t(
                                                'Refund {{amount}} to the customer?',
                                                {
                                                    amount: expectedAmount
                                                        ? `€${expectedAmount}`
                                                        : '—',
                                                },
                                            )}
                                            confirmLabel={t(
                                                'Full refund',
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
                                                {t('Full refund')}
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
                                                {t('Cancel')}
                                            </Button>
                                        </Form>
                                    ) : null}
                                </div>
                            </AdminPanel>
                        )}

                        {orderReturn.can_refund ? (
                            <AdminPanel title={t('Partial refund')}>
                                <div className="space-y-3">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="refund_amount">
                                            {t('Partial amount')}
                                        </Label>
                                        <Input
                                            id="refund_amount"
                                            name="refund_amount"
                                            type="number"
                                            min="0.01"
                                            max={expectedAmount || undefined}
                                            step="0.01"
                                            required
                                            value={partialAmount}
                                            onChange={(event) =>
                                                setPartialAmount(
                                                    event.target.value,
                                                )
                                            }
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
                                            {t('Reason')}
                                        </Label>
                                        <Textarea
                                            id="partial_reason"
                                            name="refund_reason"
                                            required
                                            rows={3}
                                            value={partialReason}
                                            onChange={(event) =>
                                                setPartialReason(
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <ConfirmDeleteDialog
                                        title={t('Confirm refund')}
                                        description={t(
                                            'Refund {{amount}} to the customer?',
                                            {
                                                amount: partialAmount
                                                    ? `€${partialAmount}`
                                                    : '—',
                                            },
                                        )}
                                        confirmLabel={t(
                                            'Partial refund',
                                        )}
                                        onConfirm={() => {
                                            if (
                                                !partialAmount ||
                                                !partialReason.trim()
                                            ) {
                                                return;
                                            }

                                            router.post(
                                                `/${locale}/admin/returns/${orderReturn.id}/partial-refund`,
                                                {
                                                    refund_amount:
                                                        partialAmount,
                                                    refund_reason:
                                                        partialReason,
                                                },
                                            );
                                        }}
                                    >
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="w-full"
                                            disabled={
                                                !partialAmount ||
                                                !partialReason.trim()
                                            }
                                        >
                                            {t('Partial refund')}
                                        </Button>
                                    </ConfirmDeleteDialog>
                                </div>
                            </AdminPanel>
                        ) : null}

                        {orderReturn.can_decline ? (
                            <AdminPanel title={t('Decline')}>
                                <Form
                                    action={`/${locale}/admin/returns/${orderReturn.id}/decline`}
                                    method="post"
                                    className="space-y-3"
                                >
                                    <div className="space-y-1.5">
                                        <Label htmlFor="decline_reason">
                                            {t('Reason')}
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
                                        {t('Decline')}
                                    </Button>
                                </Form>
                            </AdminPanel>
                        ) : null}

                        <AdminPanel title={t('Tracking')}>
                            {!editingTracking &&
                            orderReturn.tracking_number ? (
                                <div className="space-y-3">
                                    <dl className="space-y-2 text-sm">
                                        <div className="flex gap-3">
                                            <dt className="w-28 shrink-0 text-muted-foreground">
                                                {t('Carrier')}
                                            </dt>
                                            <dd className="font-medium">
                                                {orderReturn.carrier || '—'}
                                            </dd>
                                        </div>
                                        <div className="flex gap-3">
                                            <dt className="w-28 shrink-0 text-muted-foreground">
                                                {t('Tracking number')}
                                            </dt>
                                            <dd className="font-medium break-all">
                                                {orderReturn.tracking_number}
                                            </dd>
                                        </div>
                                    </dl>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full"
                                        onClick={() =>
                                            setEditingTracking(true)
                                        }
                                    >
                                        {t('Edit tracking')}
                                    </Button>
                                </div>
                            ) : (
                                <Form
                                    action={`/${locale}/admin/returns/${orderReturn.id}/tracking`}
                                    method="post"
                                    className="space-y-3"
                                >
                                    <div className="space-y-1.5">
                                        <Label htmlFor="carrier">
                                            {t('Carrier')}
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
                                            {t('Tracking number')}
                                        </Label>
                                        <Input
                                            id="tracking_number"
                                            name="tracking_number"
                                            defaultValue={
                                                orderReturn.tracking_number ??
                                                ''
                                            }
                                            required
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        className="w-full"
                                    >
                                        {t('Save tracking')}
                                    </Button>
                                </Form>
                            )}
                        </AdminPanel>

                        <AdminPanel title={t('Extend deadline')}>
                            <Form
                                action={`/${locale}/admin/returns/${orderReturn.id}/extend`}
                                method="post"
                                className="space-y-3"
                            >
                                <div className="space-y-1.5">
                                    <Label htmlFor="return_deadline_at">
                                        {t('New deadline')}
                                    </Label>
                                    <AdminDatePicker
                                        id="return_deadline_at"
                                        name="return_deadline_at"
                                        value={deadline}
                                        onChange={setDeadline}
                                        required
                                        disablePast
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    variant="outline"
                                    className="w-full"
                                    disabled={!deadline}
                                >
                                    {t('Save')}
                                </Button>
                            </Form>
                        </AdminPanel>

                        <AdminPanel title={t('Note')}>
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
                                    {t('Add note')}
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
                                    {t('Reason')}
                                </dt>
                                <dd className="font-medium">
                                    {orderReturn.reason
                                        ? t(orderReturn.reason)
                                        : '—'}
                                </dd>
                            </div>
                            <div className="flex gap-3">
                                <dt className="w-28 shrink-0 text-muted-foreground">
                                    {t('Carrier')}
                                </dt>
                                <dd className="font-medium">
                                    {orderReturn.carrier || '—'}
                                </dd>
                            </div>
                            <div className="flex gap-3">
                                <dt className="w-28 shrink-0 text-muted-foreground">
                                    {t('Tracking number')}
                                </dt>
                                <dd className="font-medium break-all">
                                    {orderReturn.tracking_number || '—'}
                                </dd>
                            </div>
                            <div className="flex gap-3">
                                <dt className="w-28 shrink-0 text-muted-foreground">
                                    {t('Expected')}
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
                                        {t('Refunded')}
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
                                    'Damaged — return shipping at our expense',
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
                                {t('View order')}
                            </Link>
                        </div>
                    </AdminPanel>

                    {orderReturn.photos.length > 0 ? (
                        <AdminPanel title={t("Photos")}>
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
                                {t('No events yet.')}
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
            </div>
        </>
    );
}
