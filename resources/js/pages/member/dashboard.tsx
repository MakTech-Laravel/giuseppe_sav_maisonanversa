import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';

type Member = {
    name: string;
    username: string;
    email: string;
    editionNumber: string;
    orderStatus: string;
    reservedAt: string;
};

type Stat = { label: string; value: string; hint: string };

type RecentOrder = {
    id: string;
    reference?: string;
    label: string;
    status: string;
    amount: string;
};

export default function MemberDashboard({
    member,
    stats,
    recentOrders = [],
}: {
    member: Member;
    stats: Stat[];
    recentOrders?: RecentOrder[];
}) {
    const { t } = useTranslation();
    const { locale, auth } = usePage().props;
    const isFoundingCircle = Boolean(auth?.user?.is_founding_circle);

    const links = [
        {
            href: `/${locale}/member/orders`,
            label: t('Orders'),
            hint: t('Payment history'),
        },
        ...(isFoundingCircle
            ? [
                  {
                      href: `/${locale}/member/circle`,
                      label: t('Founding Circle'),
                      hint: t('Your membership card'),
                  },
              ]
            : []),
        {
            href: `/${locale}/member/profile`,
            label: t('Profile'),
            hint: t('Name, photo, email'),
        },
        {
            href: `/${locale}/member/letter`,
            label: t('Heritage Letter'),
            hint: t('Your subscriptions'),
        },
        {
            href: `/${locale}/member/email-preferences`,
            label: t('Email preferences'),
            hint: t('Preferences'),
        },
    ] as const;

    return (
        <>
            <Head title={t('Dashboard')} />
            <MemberPageHeader
                eyebrow={t('Founding Circle')}
                title={t('Welcome, {{name}}', { name: member.name })}
                description={t(
                    'Your rooms in the house — orders, the Circle and account settings.',
                )}
            />

            <div className="mb-10 grid gap-4 md:grid-cols-3">
                {stats.map((stat) => (
                    <MemberPanel key={stat.label}>
                        <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                            {stat.label}
                        </p>
                        <p className="mt-2 font-serif text-[28px] text-cream">
                            {stat.value}
                        </p>
                        <p className="mt-1 text-[13px] text-sand">
                            {stat.hint}
                        </p>
                    </MemberPanel>
                ))}
            </div>

            {recentOrders.length > 0 && (
                <MemberPanel className="mb-10">
                    <div className="mb-4 flex items-center justify-between gap-3">
                        <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                            {t('Recent orders')}
                        </p>
                        <Link
                            href={`/${locale}/member/orders`}
                            className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase no-underline hover:text-cream"
                        >
                            {t('View all')}
                        </Link>
                    </div>
                    <ul className="divide-y divide-gold/10">
                        {recentOrders.map((order) => (
                            <li
                                key={order.id}
                                className="flex items-center justify-between gap-3 py-3"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-[15px] text-cream">
                                        {order.label}
                                    </p>
                                    <p className="mt-1 font-sans text-[11px] text-stone">
                                        {order.reference ?? order.id} ·{' '}
                                        {order.status}
                                    </p>
                                </div>
                                <Link
                                    href={`/${locale}/member/orders/${order.id}`}
                                    className="shrink-0 font-sans text-[10px] tracking-[0.16em] text-gold uppercase no-underline hover:text-cream"
                                >
                                    {t('View')}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </MemberPanel>
            )}

            <div className="grid gap-4 md:grid-cols-2">
                {links.map((link) => (
                    <Link
                        key={link.href}
                        href={link.href}
                        className="border border-gold/25 bg-choc3 p-6 no-underline transition-colors hover:border-gold"
                    >
                        <p className="font-serif text-[22px] text-cream">
                            {link.label}
                        </p>
                        <p className="mt-1 font-sans text-[11px] tracking-[0.12em] text-stone uppercase">
                            {link.hint}
                        </p>
                    </Link>
                ))}
            </div>
        </>
    );
}
