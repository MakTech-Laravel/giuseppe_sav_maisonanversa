import { Head, Link, usePage } from '@inertiajs/react';
import {
    MemberPageHeader,
    MemberPanel,
    MemberSectionTitle,
    MemberStatusPill,
} from '@/components/member/member-ui';

type OrderDetail = {
    id: string;
    label: string;
    date: string;
    amount: string;
    status: string;
    method: string;
    summary: string;
    items: { name: string; qty: number; price: string }[];
    billing: { name: string; email: string; address: string };
    timeline: { label: string; at: string; done: boolean }[];
};

export default function MemberOrderShow({ order }: { order: OrderDetail }) {
    const { locale } = usePage().props;

    return (
        <>
            <Head title={`Order ${order.id}`} />
            <div className="mb-6">
                <Link
                    href={`/${locale}/member/orders`}
                    className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase no-underline hover:text-cream"
                >
                    ← Back to orders
                </Link>
            </div>

            <MemberPageHeader
                eyebrow="Order detail"
                title={order.label}
                description={order.summary}
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <MemberStatusPill
                    tone={
                        order.status.toLowerCase() === 'paid'
                            ? 'success'
                            : 'neutral'
                    }
                >
                    {order.status}
                </MemberStatusPill>
                <p className="font-sans text-[11px] tracking-[0.14em] text-sand uppercase">
                    {order.id}
                </p>
                <p className="font-sans text-[11px] tracking-[0.14em] text-stone uppercase">
                    {order.date}
                </p>
            </div>

            <div className="grid gap-6 lg:grid-cols-5">
                <MemberPanel className="lg:col-span-3">
                    <MemberSectionTitle title="Line items" />
                    <ul className="space-y-4">
                        {order.items.map((item) => (
                            <li
                                key={item.name}
                                className="flex items-start justify-between gap-4 border-b border-gold/10 pb-4 last:border-0 last:pb-0"
                            >
                                <div>
                                    <p className="text-[15px] text-cream">
                                        {item.name}
                                    </p>
                                    <p className="mt-1 font-sans text-[11px] text-stone uppercase">
                                        Qty {item.qty}
                                    </p>
                                </div>
                                <p className="font-serif text-[20px] text-cream">
                                    {item.price}
                                </p>
                            </li>
                        ))}
                    </ul>
                    <div className="mt-6 flex items-center justify-between border-t border-gold/20 pt-4">
                        <p className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase">
                            Total
                        </p>
                        <p className="font-serif text-[28px] text-cream">
                            {order.amount}
                        </p>
                    </div>
                </MemberPanel>

                <div className="space-y-6 lg:col-span-2">
                    <MemberPanel>
                        <MemberSectionTitle title="Payment" />
                        <dl className="space-y-3 text-[14px]">
                            <div>
                                <dt className="font-sans text-[9px] tracking-[0.18em] text-gold uppercase">
                                    Method
                                </dt>
                                <dd className="mt-1 text-cream">
                                    {order.method}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-sans text-[9px] tracking-[0.18em] text-gold uppercase">
                                    Amount
                                </dt>
                                <dd className="mt-1 font-serif text-[22px] text-cream">
                                    {order.amount}
                                </dd>
                            </div>
                        </dl>
                    </MemberPanel>

                    <MemberPanel>
                        <MemberSectionTitle title="Billing" />
                        <dl className="space-y-3 text-[14px] text-sand">
                            <div>
                                <dt className="font-sans text-[9px] tracking-[0.18em] text-gold uppercase">
                                    Name
                                </dt>
                                <dd className="mt-1 text-cream">
                                    {order.billing.name}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-sans text-[9px] tracking-[0.18em] text-gold uppercase">
                                    Email
                                </dt>
                                <dd className="mt-1">{order.billing.email}</dd>
                            </div>
                            <div>
                                <dt className="font-sans text-[9px] tracking-[0.18em] text-gold uppercase">
                                    Address
                                </dt>
                                <dd className="mt-1">
                                    {order.billing.address}
                                </dd>
                            </div>
                        </dl>
                    </MemberPanel>
                </div>
            </div>

            <MemberPanel className="mt-6">
                <MemberSectionTitle
                    title="Timeline"
                    description="Prototype milestones until fulfilment is connected."
                />
                <ol className="space-y-4">
                    {order.timeline.map((step) => (
                        <li
                            key={step.label}
                            className="flex items-start gap-4"
                        >
                            <span
                                aria-hidden="true"
                                className={
                                    step.done
                                        ? 'mt-1.5 size-2.5 shrink-0 bg-gold'
                                        : 'mt-1.5 size-2.5 shrink-0 border border-gold/40'
                                }
                            />
                            <div>
                                <p className="text-[15px] text-cream">
                                    {step.label}
                                </p>
                                <p className="mt-0.5 font-sans text-[11px] tracking-[0.12em] text-stone uppercase">
                                    {step.at}
                                </p>
                            </div>
                        </li>
                    ))}
                </ol>
            </MemberPanel>
        </>
    );
}
