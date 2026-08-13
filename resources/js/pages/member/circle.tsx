import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';

type Card = { number: string; name: string; since: string };

export default function MemberCircle({ card }: { card: Card }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Founding Circle')} />
            <MemberPageHeader
                eyebrow={t('Lidmaatschap')}
                title={t('Founding Circle-kaart')}
                description={t(
                    'Uw digitale kaart — het nummer dat niet opnieuw wordt uitgegeven.',
                )}
            />

            <MemberPanel className="mx-auto max-w-md bg-choc p-10 text-center">
                <p className="font-sans text-[9px] tracking-[0.35em] text-gold uppercase">
                    Maison Anversa
                </p>
                <p className="mt-6 font-serif text-[64px] leading-none tracking-[0.08em] text-gold">
                    {card.number}
                </p>
                <p className="mt-6 font-serif text-[24px] text-cream">{card.name}</p>
                <p className="mt-2 font-sans text-[10px] tracking-[0.2em] text-sand uppercase">
                    {t('Founding Circle · sinds {{since}}', {
                        since: card.since,
                    })}
                </p>
                <span
                    aria-hidden="true"
                    className="mx-auto mt-8 block h-px w-12 bg-gold"
                />
                <p className="mt-6 font-sans text-[10px] tracking-[0.18em] text-sand uppercase">
                    {t('Permanent lidmaatschap')}
                </p>
            </MemberPanel>
        </>
    );
}
