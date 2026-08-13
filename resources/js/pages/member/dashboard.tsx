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

export default function MemberDashboard({
    member,
    stats,
}: {
    member: Member;
    stats: Stat[];
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    const links = [
        {
            href: `/${locale}/member/orders`,
            label: t('Bestellingen'),
            hint: t('Betalingsgeschiedenis'),
        },
        {
            href: `/${locale}/member/circle`,
            label: t('Founding Circle'),
            hint: t('Uw lidmaatschapskaart'),
        },
        {
            href: `/${locale}/member/profile`,
            label: t('Profiel'),
            hint: t('Naam, foto, e-mail'),
        },
        {
            href: `/${locale}/member/letter`,
            label: t('Heritage Letter'),
            hint: t('Voorkeuren'),
        },
    ] as const;

    return (
        <>
            <Head title={t('Dashboard')} />
            <MemberPageHeader
                eyebrow={t('Founding Circle')}
                title={t('Welkom, {{name}}', { name: member.name })}
                description={t(
                    'Uw kamers in het huis — bestellingen, de Circle en accountinstellingen.',
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
