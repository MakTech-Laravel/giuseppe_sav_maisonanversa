import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    MemberPageHeader,
    MemberPanel,
    MemberStatusPill,
} from '@/components/member/member-ui';

type Subscription = {
    id: number;
    email: string;
    source: string;
    status: string;
    preferences: {
        heritageLetter: boolean;
        productUpdates: boolean;
        events: boolean;
    };
    joined_at: string | null;
};

const SOURCE_LABELS: Record<string, string> = {
    home: 'Home',
    story: 'Verhaal',
    modal: 'Modal',
    waitlist: 'Wachtlijst',
    sold_out: 'Uitverkocht',
    member: 'Lid',
};

const STATUS_LABELS: Record<string, string> = {
    pending: 'In behandeling',
    subscribed: 'Actief',
    unsubscribed: 'Uitgeschreven',
};

export default function MemberLetter({
    subscriptions,
}: {
    subscriptions: Subscription[];
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    const topics = [
        ['heritageLetter', t('Heritage Letter')],
        ['productUpdates', t('Productupdates')],
        ['events', t('Sessies & events')],
    ] as const;

    return (
        <>
            <Head title={t('Heritage Letter')} />
            <MemberPageHeader
                eyebrow={t('De Heritage Letter')}
                title={t('Heritage Letter')}
                description={t(
                    'Alle inschrijvingen die aan dit account gekoppeld zijn.',
                )}
            />

            {subscriptions.length === 0 ? (
                <MemberPanel className="max-w-xl">
                    <p className="text-[15px] leading-relaxed text-sand">
                        {t('U staat nog niet op de Heritage Letter.')}
                    </p>
                    <Link
                        href={`/${locale}/member/email-preferences`}
                        className="mt-5 inline-block font-sans text-[11px] tracking-[0.14em] text-gold uppercase"
                    >
                        {t('Beheer e-mailvoorkeuren')}
                    </Link>
                </MemberPanel>
            ) : (
                <div className="space-y-4">
                    {subscriptions.map((subscription) => (
                        <MemberPanel key={subscription.id}>
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p className="font-serif text-[22px] text-cream">
                                        {subscription.email}
                                    </p>
                                    <p className="mt-1 font-sans text-[11px] tracking-[0.12em] text-stone uppercase">
                                        {t(
                                            SOURCE_LABELS[subscription.source] ??
                                                subscription.source,
                                        )}
                                        {subscription.joined_at
                                            ? ` · ${subscription.joined_at}`
                                            : ''}
                                    </p>
                                </div>
                                <MemberStatusPill
                                    tone={
                                        subscription.status === 'subscribed'
                                            ? 'success'
                                            : 'warn'
                                    }
                                >
                                    {t(
                                        STATUS_LABELS[subscription.status] ??
                                            subscription.status,
                                    )}
                                </MemberStatusPill>
                            </div>

                            <div className="mt-5 flex flex-wrap gap-2">
                                {topics
                                    .filter(
                                        ([key]) =>
                                            subscription.preferences[key],
                                    )
                                    .map(([key, label]) => (
                                        <span
                                            key={key}
                                            className="border border-gold/30 px-2 py-1 font-sans text-[10px] tracking-[0.14em] text-sand uppercase"
                                        >
                                            {label}
                                        </span>
                                    ))}
                            </div>
                        </MemberPanel>
                    ))}

                    <p className="pt-2">
                        <Link
                            href={`/${locale}/member/email-preferences`}
                            className="font-sans text-[11px] tracking-[0.14em] text-gold uppercase"
                        >
                            {t('Beheer e-mailvoorkeuren')}
                        </Link>
                    </p>
                </div>
            )}
        </>
    );
}
