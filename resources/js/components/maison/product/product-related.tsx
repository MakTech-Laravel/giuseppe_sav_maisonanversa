import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { ImageAssetName } from '@/lib/imagery';
import type { MaisonPage } from '@/lib/maison-navigation';

type RelatedEdition = {
    asset: ImageAssetName;
    edition: string;
    name: string;
    status: string;
    to?: MaisonPage;
};

const RELATED: readonly RelatedEdition[] = [
    {
        asset: 'heritage-001-front',
        edition: 'Heritage No.002',
        name: 'Binnenkort',
        status: 'Volgende release · Aankondiging via de Heritage Letter',
    },
    {
        asset: 'heritage-001-lifestyle-court',
        edition: 'Heritage No.003',
        name: 'Binnenkort',
        status: 'In voorbereiding · Geen datum bekend',
    },
    {
        asset: 'atelier-workshop',
        edition: 'Het Huis',
        name: 'Meer van ons',
        status: 'Ontdek het volledige verhaal van Maison Anversa',
        to: 'house',
    },
];

export function ProductRelated() {
    const { t } = useTranslation();

    return (
        <Section tone="dark">
            <Wrap>
                <Reveal className="mb-13 text-center">
                    <Eyebrow>{t('Volgende Hoofdstukken')}</Eyebrow>
                    <GoldRule center className="mx-auto" />
                    <h2 className="font-serif text-[clamp(28px,3.5vw,42px)] font-medium tracking-[0.04em] text-cream uppercase">
                        {t('De volgende nummers.')}
                    </h2>
                </Reveal>

                <div className="grid gap-6 md:grid-cols-3">
                    {RELATED.map((card) => {
                        const body = (
                            <>
                                <div className="relative aspect-4/5 overflow-hidden">
                                    <PlaceholderImage
                                        asset={card.asset}
                                        ratio={null}
                                        alt={card.edition}
                                        captioned={false}
                                        className="absolute inset-0 h-full w-full"
                                    />
                                    <div
                                        aria-hidden="true"
                                        className="pointer-events-none absolute inset-0 bg-linear-to-t from-choc/55 to-transparent to-60%"
                                    />
                                </div>
                                <div className="px-6.5 py-7.5">
                                    <div className="font-sans text-[9px] tracking-[0.25em] text-gold uppercase">
                                        {card.edition === 'Het Huis'
                                            ? t(card.edition)
                                            : card.edition}
                                    </div>
                                    <div className="my-2 font-serif text-[25px] text-cream">
                                        {t(card.name)}
                                    </div>
                                    <div className="text-xs leading-[1.6] text-sand">
                                        {t(card.status)}
                                    </div>
                                </div>
                            </>
                        );

                        return (
                            <Reveal
                                key={card.edition}
                                className="overflow-hidden bg-choc2"
                            >
                                {card.to ? (
                                    <MaisonLink
                                        to={card.to}
                                        className="block transition-opacity hover:opacity-90"
                                    >
                                        {body}
                                    </MaisonLink>
                                ) : (
                                    body
                                )}
                            </Reveal>
                        );
                    })}
                </div>
            </Wrap>
        </Section>
    );
}
