import { Head, Link, usePage } from '@inertiajs/react';
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

export default function MemberDashboard({
    member,
    stats,
}: {
    member: Member;
    stats: Stat[];
}) {
    const { locale } = usePage().props;

    const links = [
        {
            href: `/${locale}/member/orders`,
            label: 'Orders',
            hint: 'Payment history',
        },
        {
            href: `/${locale}/member/circle`,
            label: 'Founding Circle',
            hint: 'Your membership card',
        },
        {
            href: `/${locale}/member/profile`,
            label: 'Profile',
            hint: 'Name, photo, email',
        },
        {
            href: `/${locale}/member/letter`,
            label: 'Heritage Letter',
            hint: 'Preferences',
        },
    ] as const;

    return (
        <>
            <Head title="Member Dashboard" />
            <MemberPageHeader
                eyebrow="Founding Circle"
                title={`Welcome, ${member.name}`}
                description="Your rooms in the house — orders, the Circle, and account settings."
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
                        <p className="mt-1 text-[13px] text-sand">{stat.hint}</p>
                    </MemberPanel>
                ))}
            </div>

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
