import { Head, Link } from '@inertiajs/react';
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

const LINKS = [
    { href: '/member/heritage', label: 'My Heritage', hint: 'Edition & delivery' },
    { href: '/member/passport', label: 'Passport', hint: 'Digital Heritage Passport' },
    { href: '/member/circle', label: 'Founding Circle', hint: 'Your membership card' },
    { href: '/member/orders', label: 'Orders', hint: 'Payment history' },
    { href: '/member/profile', label: 'Profile', hint: 'Name, username, email' },
    { href: '/member/letter', label: 'Heritage Letter', hint: 'Preferences' },
] as const;

export default function MemberDashboard({
    member,
    stats,
}: {
    member: Member;
    stats: Stat[];
}) {
    return (
        <>
            <Head title="Member Dashboard" />
            <MemberPageHeader
                eyebrow="Founding Circle"
                title={`Welcome, ${member.name}`}
                description="Your rooms in the house — Heritage No.001, the passport, and the Circle."
            />

            <div className="mb-10 grid gap-4 md:grid-cols-3">
                {stats.map((stat) => (
                    <MemberPanel key={stat.label}>
                        <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                            {stat.label}
                        </p>
                        <p className="mt-2 font-serif text-[28px] text-choc">
                            {stat.value}
                        </p>
                        <p className="mt-1 text-[13px] text-choc3">{stat.hint}</p>
                    </MemberPanel>
                ))}
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                {LINKS.map((link) => (
                    <Link
                        key={link.href}
                        href={link.href}
                        className="border border-gold/20 bg-cream2 p-6 no-underline transition-colors hover:border-gold"
                    >
                        <p className="font-serif text-[22px] text-choc">
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
