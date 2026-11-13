import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    MemberEmptyState,
    MemberPageHeader,
    MemberPanel,
} from '@/components/member/member-ui';

type Card = {
    number: string;
    name: string;
    since: string;
    productName: string;
    racketLabel: string;
};

export default function MemberCircle({ card }: { card: Card | null }) {
    const { t } = useTranslation();

    if (card === null) {
        return (
            <>
                <Head title={t('Founding Circle')} />
                <MemberPageHeader
                    eyebrow={t('Membership')}
                    title={t('Founding Circle card')}
                />
                <MemberEmptyState
                    title={t('No edition assigned yet')}
                    description={t(
                        'You are a Founding Circle member, but no Heritage edition is linked to your account yet. Contact the team for more information.',
                    )}
                />
            </>
        );
    }

    return (
        <>
            <Head title={t('Founding Circle')} />
            <MemberPageHeader
                eyebrow={t('Membership')}
                title={t('Founding Circle card')}
                description={t(
                    'Your digital card — the number that will not be reissued.',
                )}
            />

            <MemberPanel className="mx-auto max-w-md bg-choc p-10 text-center">
                <p className="font-sans text-[9px] tracking-[0.35em] text-gold uppercase">
                    {t('Founding Circle')}
                </p>
                <p className="mt-8 font-sans text-[11px] tracking-[0.28em] text-cream uppercase">
                    {card.productName}
                </p>
                <p className="mt-4 font-serif text-[40px] leading-none tracking-[0.08em] text-gold">
                    {t('Racket {{label}}', { label: card.racketLabel })}
                </p>
                <p className="mt-8 font-serif text-[24px] text-cream">
                    {card.name}
                </p>
                <p className="mt-2 font-sans text-[10px] tracking-[0.2em] text-sand uppercase">
                    {t('Founding Circle · Since {{since}}', {
                        since: card.since,
                    })}
                </p>
                <span
                    aria-hidden="true"
                    className="mx-auto mt-8 block h-px w-12 bg-gold"
                />
                <p className="mt-6 font-sans text-[10px] tracking-[0.18em] text-sand uppercase">
                    {t('Permanent membership')}
                </p>
            </MemberPanel>
        </>
    );
}
