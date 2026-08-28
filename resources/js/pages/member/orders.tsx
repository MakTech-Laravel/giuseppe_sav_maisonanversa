import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    MemberPageHeader,
    MemberPanel,
    MemberStatusPill,
} from '@/components/member/member-ui';

type Order = {
    id: string;
    label: string;
    date: string;
    amount: string;
    status: string;
    status_key: string;
    method: string;
};

export default function MemberOrders({ orders }: { orders: Order[] }) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    return (
        <>
            <Head title={t('Bestellingen')} />
            <MemberPageHeader
                eyebrow={t('Account')}
                title={t('Bestellingen & betalingen')}
                description={t(
                    'Uw aankopen, betalingen en verzendstatus in één overzicht.',
                )}
            />

            <MemberPanel className="p-0 md:overflow-x-auto">
                <div className="divide-y divide-gold/10 md:hidden">
                    {orders.map((order) => (
                        <div key={order.id} className="space-y-3 px-5 py-5">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-[15px] text-cream">
                                        {order.label}
                                    </p>
                                    <p className="mt-1 font-sans text-[11px] text-stone">
                                        {order.id} · {order.method}
                                    </p>
                                </div>
                                <p className="shrink-0 font-serif text-[18px] text-cream">
                                    {order.amount}
                                </p>
                            </div>
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div className="flex items-center gap-3">
                                    <MemberStatusPill
                                        tone={
                                            order.status_key === 'paid'
                                                ? 'success'
                                                : 'neutral'
                                        }
                                    >
                                        {order.status}
                                    </MemberStatusPill>
                                    <p className="text-[13px] text-sand">
                                        {order.date}
                                    </p>
                                </div>
                                <Link
                                    href={`/${locale}/member/orders/${order.id}`}
                                    className="inline-flex min-h-9 items-center border border-gold/40 px-3 font-sans text-[10px] tracking-[0.16em] text-gold uppercase no-underline transition-colors hover:bg-gold hover:text-choc"
                                >
                                    {t('Bekijken')}
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>

                <table className="hidden w-full min-w-180 text-left md:table">
                    <thead>
                        <tr className="border-b border-gold/20 font-sans text-[9px] tracking-[0.2em] text-gold uppercase">
                            <th className="px-6 py-4 font-medium">
                                {t('Referentie')}
                            </th>
                            <th className="px-6 py-4 font-medium">
                                {t('Artikel')}
                            </th>
                            <th className="px-6 py-4 font-medium">
                                {t('Datum')}
                            </th>
                            <th className="px-6 py-4 font-medium">
                                {t('Bedrag')}
                            </th>
                            <th className="px-6 py-4 font-medium">
                                {t('Status')}
                            </th>
                            <th className="px-6 py-4 text-right font-medium">
                                {t('Actie')}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {orders.map((order) => (
                            <tr
                                key={order.id}
                                className="border-b border-gold/10 last:border-0"
                            >
                                <td className="px-6 py-4 font-sans text-[12px] text-sand">
                                    {order.id}
                                </td>
                                <td className="px-6 py-4">
                                    <p className="text-[15px] text-cream">
                                        {order.label}
                                    </p>
                                    <p className="font-sans text-[11px] text-stone">
                                        {order.method}
                                    </p>
                                </td>
                                <td className="px-6 py-4 text-[14px] text-sand">
                                    {order.date}
                                </td>
                                <td className="px-6 py-4 font-serif text-[18px] text-cream">
                                    {order.amount}
                                </td>
                                <td className="px-6 py-4">
                                    <MemberStatusPill
                                        tone={
                                            order.status_key === 'paid'
                                                ? 'success'
                                                : 'neutral'
                                        }
                                    >
                                        {order.status}
                                    </MemberStatusPill>
                                </td>
                                <td className="px-6 py-4 text-right">
                                    <Link
                                        href={`/${locale}/member/orders/${order.id}`}
                                        className="inline-flex min-h-9 items-center border border-gold/40 px-3 font-sans text-[10px] tracking-[0.16em] text-gold uppercase no-underline transition-colors hover:bg-gold hover:text-choc"
                                    >
                                        {t('Bekijken')}
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </MemberPanel>
        </>
    );
}
