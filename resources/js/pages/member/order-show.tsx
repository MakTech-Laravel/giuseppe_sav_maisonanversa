import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    MemberPageHeader,
    MemberPanel,
    MemberSectionTitle,
    MemberStatusPill,
} from '@/components/member/member-ui';

type OrderDetail = {
    id: string;
    reference?: string;
    label: string;
    date: string;
    amount: string;
    status: string;
    status_key: string;
    method: string;
    summary: string;
    items: { name: string; qty: number; price: string }[];
    shipping?: {
        line1: string;
        line2?: string | null;
        city: string;
        postal_code: string;
        country: string;
    };
    timeline: { label: string; at: string; done: boolean }[];
    events?: {
        id: string;
        status: string;
        message: string | null;
        at: string;
    }[];
};

export default function MemberOrderShow({ order }: { order: OrderDetail }) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    return (
        <>
            <Head title={`${t('Bestelling')} ${order.reference ?? order.id}`} />
            <div className="mb-6">
                <Link
                    href={`/${locale}/member/orders`}
                    className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase no-underline hover:text-cream"
                >
                    ← {t('Terug naar bestellingen')}
                </Link>
            </div>

            <MemberPageHeader
                eyebrow={t('Besteldetail')}
                title={order.label}
                description={order.summary}
            />

            <div className="mb-6 flex flex-wrap items-center gap-3">
                <MemberStatusPill
                    tone={
                        ['paid', 'processing', 'shipped', 'delivered'].includes(
                            order.status_key,
                        )
                            ? 'success'
                            : 'neutral'
                    }
                >
                    {order.status}
                </MemberStatusPill>
                <p className="font-sans text-[11px] tracking-[0.14em] text-sand uppercase">
                    {order.reference ?? order.id}
                </p>
                <p className="font-sans text-[11px] tracking-[0.14em] text-stone uppercase">
                    {order.date}
                </p>
            </div>

            <div className="grid gap-6 lg:grid-cols-5">
                <MemberPanel className="lg:col-span-3">
                    <MemberSectionTitle title={t('Regels')} />
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
                                        {t('Aantal')} {item.qty}
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
                            {t('Totaal')}
                        </p>
                        <p className="font-serif text-[28px] text-cream">
                            {order.amount}
                        </p>
                    </div>
                </MemberPanel>

                <div className="space-y-6 lg:col-span-2">
                    <MemberPanel>
                        <MemberSectionTitle title={t('Betaling')} />
                        <dl className="space-y-3 text-[14px]">
                            <div>
                                <dt className="font-sans text-[9px] tracking-[0.18em] text-gold uppercase">
                                    {t('Methode')}
                                </dt>
                                <dd className="mt-1 text-cream">
                                    {order.method}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-sans text-[9px] tracking-[0.18em] text-gold uppercase">
                                    {t('Bedrag')}
                                </dt>
                                <dd className="mt-1 font-serif text-[22px] text-cream">
                                    {order.amount}
                                </dd>
                            </div>
                        </dl>
                    </MemberPanel>

                    {order.shipping && (
                        <MemberPanel>
                            <MemberSectionTitle title={t('Verzendadres')} />
                            <p className="text-[14px] text-sand">
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
                        </MemberPanel>
                    )}
                </div>
            </div>

            <MemberPanel className="mt-6">
                <MemberSectionTitle title={t('Tijdlijn')} />
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
                                    {step.at || '—'}
                                </p>
                            </div>
                        </li>
                    ))}
                </ol>
            </MemberPanel>

            {order.events && order.events.length > 0 && (
                <MemberPanel className="mt-6">
                    <MemberSectionTitle title={t('Updates')} />
                    <ul className="space-y-4">
                        {order.events.map((event) => (
                            <li
                                key={event.id}
                                className="border-b border-gold/10 pb-4 last:border-0 last:pb-0"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <p className="font-sans text-[11px] tracking-[0.14em] text-gold uppercase">
                                        {event.status}
                                    </p>
                                    <p className="font-sans text-[11px] text-stone">
                                        {event.at}
                                    </p>
                                </div>
                                <p className="mt-2 text-[14px] text-sand">
                                    {event.message || '—'}
                                </p>
                            </li>
                        ))}
                    </ul>
                </MemberPanel>
            )}
        </>
    );
}
