import { Head } from '@inertiajs/react';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';

type Order = {
    id: string;
    label: string;
    date: string;
    amount: string;
    status: string;
    method: string;
};

export default function MemberOrders({ orders }: { orders: Order[] }) {
    return (
        <>
            <Head title="Orders" />
            <MemberPageHeader
                eyebrow="Account"
                title="Orders & payments"
                description="Prototype history until Stripe is connected — figures shown for layout only."
            />

            <MemberPanel className="overflow-x-auto p-0">
                <table className="w-full min-w-150 text-left">
                    <thead>
                        <tr className="border-b border-gold/20 font-sans text-[9px] tracking-[0.2em] text-gold uppercase">
                            <th className="px-6 py-4 font-medium">Reference</th>
                            <th className="px-6 py-4 font-medium">Item</th>
                            <th className="px-6 py-4 font-medium">Date</th>
                            <th className="px-6 py-4 font-medium">Amount</th>
                            <th className="px-6 py-4 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {orders.map((order) => (
                            <tr
                                key={order.id}
                                className="border-b border-gold/10 last:border-0"
                            >
                                <td className="px-6 py-4 font-sans text-[12px] text-choc3">
                                    {order.id}
                                </td>
                                <td className="px-6 py-4">
                                    <p className="text-[15px] text-choc">
                                        {order.label}
                                    </p>
                                    <p className="font-sans text-[11px] text-stone">
                                        {order.method}
                                    </p>
                                </td>
                                <td className="px-6 py-4 text-[14px] text-choc3">
                                    {order.date}
                                </td>
                                <td className="px-6 py-4 font-serif text-[18px] text-choc">
                                    {order.amount}
                                </td>
                                <td className="px-6 py-4 font-sans text-[10px] tracking-[0.16em] text-gold2 uppercase">
                                    {order.status}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </MemberPanel>
        </>
    );
}
