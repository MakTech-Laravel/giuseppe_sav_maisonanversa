import { Head, Link, usePage } from '@inertiajs/react';
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
    method: string;
};

export default function MemberOrders({ orders }: { orders: Order[] }) {
    const { locale } = usePage().props;

    return (
        <>
            <Head title="Orders" />
            <MemberPageHeader
                eyebrow="Account"
                title="Orders & payments"
                description="Prototype history until Stripe is connected — figures shown for layout only."
            />

            <MemberPanel className="overflow-x-auto p-0">
                <table className="w-full min-w-180 text-left">
                    <thead>
                        <tr className="border-b border-gold/20 font-sans text-[9px] tracking-[0.2em] text-gold uppercase">
                            <th className="px-6 py-4 font-medium">Reference</th>
                            <th className="px-6 py-4 font-medium">Item</th>
                            <th className="px-6 py-4 font-medium">Date</th>
                            <th className="px-6 py-4 font-medium">Amount</th>
                            <th className="px-6 py-4 font-medium">Status</th>
                            <th className="px-6 py-4 text-right font-medium">
                                Action
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
                                            order.status.toLowerCase() ===
                                            'paid'
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
                                        View
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
