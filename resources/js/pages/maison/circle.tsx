import { useTranslation } from 'react-i18next';
import { CirclePortal } from '@/components/maison/circle/circle-portal';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import { usePurchasesPaused } from '@/hooks/use-purchases-paused';

const BENEFITS = [
    {
        num: '01',
        title: 'Early Access',
        desc: 'First access to all future releases and collections of Maison Anversa — at least 48 hours before the general public.',
    },
    {
        num: '02',
        title: 'Exclusive Events',
        desc: 'Personal invitations to Maison Anversa events: product presentations, padel sessions, club nights and pop-up events across Europe.',
    },
    {
        num: '03',
        title: 'Behind the Scenes',
        desc: 'Personal updates from the founder about the brand: product development, new collections, supplier trips, behind-the-scenes decisions.',
    },
    {
        num: '04',
        title: 'Name Registration',
        desc: 'Your name will be included in the official Founding Circle register — a document kept with the brand forever.',
    },
    {
        num: '05',
        title: 'Unique Edition Number',
        desc: 'Your edition number (001–100) is on the racket, certificate and passport. Yours. Forever.',
    },
    {
        num: '06',
        title: 'Priority Support',
        desc: 'All questions and requests from Founding Members are always handled first. Response time within 12 hours.',
    },
] as const;

export default function Circle() {
    const { t } = useTranslation();
    const { openPurchase } = useShellActions();
    const purchasesPaused = usePurchasesPaused();

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('The first 100')}
                title={
                    <>
                        {t('The')} <em>Founding Circle</em>
                    </>
                }
                subtitle={t(
                    'Not the first 100 customers. The first 100 people who believed.',
                )}
            />

            <Section tone="cream">
                <Wrap>
                    <Reveal className="mx-auto mb-16 max-w-160 text-center">
                        <h2 className="mb-5 font-serif text-[clamp(30px,4vw,52px)] leading-[1.1] font-medium [&_em]:text-gold2 [&_em]:italic">
                            {t('You were there at the')} <em>{t('beginning')}</em>
                        </h2>
                        <p className="text-base leading-[1.85] text-choc3">
                            {t(
                                'The Founding Circle is the permanent community of everyone who buys Heritage Nº 001. Your membership is lifelong. Your number is yours — forever. This is not a loyalty program. This is a recognition.',
                            )}
                        </p>
                    </Reveal>

                    {/*
                     * Prototype `.fc-full-benefits`: 2px sand gutters read as
                     * hairlines between cream cells, with a faint watermark
                     * number behind each benefit.
                     */}
                    <div className="mb-16 grid grid-cols-1 gap-0.5 bg-sand md:grid-cols-2">
                        {BENEFITS.map((benefit) => (
                            <Reveal
                                key={benefit.num}
                                className="relative overflow-hidden bg-cream p-6 md:p-10"
                            >
                                <span
                                    aria-hidden="true"
                                    className="pointer-events-none absolute -top-2.5 -right-2.5 font-serif text-[80px] leading-none font-bold text-gold/4"
                                >
                                    {benefit.num}
                                </span>
                                <div className="relative mb-3.5 font-serif text-[36px] leading-none font-light text-gold/25">
                                    {benefit.num}
                                </div>
                                <h3 className="relative mb-2 font-serif text-[22px] font-medium text-choc">
                                    {t(benefit.title)}
                                </h3>
                                <p className="relative text-[15px] leading-[1.7] text-choc3">
                                    {t(benefit.desc)}
                                </p>
                            </Reveal>
                        ))}
                    </div>

                    <CirclePortal />

                    <div className="mt-10 text-center">
                        <MaisonButton
                            variant="choc"
                            disabled={purchasesPaused}
                            onClick={() => openPurchase()}
                            className="max-w-full px-4 text-center whitespace-normal"
                        >
                            {t('Become a Founding Member — View Heritage Nº 001')}
                        </MaisonButton>
                        {purchasesPaused ? (
                            <p className="mt-3 font-sans text-[10px] tracking-[0.12em] text-choc3">
                                {t('Reservations are temporarily paused.')}
                            </p>
                        ) : null}
                    </div>
                </Wrap>
            </Section>
        </>
    );
}
