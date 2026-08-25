import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import { useLocale } from '@/hooks/use-locale';
import type { ImageAssetName } from '@/lib/imagery';
import { IMAGE_ASSETS } from '@/lib/imagery';
import type { MaisonPage } from '@/lib/maison-navigation';
import { maisonUrl } from '@/lib/maison-navigation';

type RelatedEdition = {
    cover_asset: string | null;
    slug: string;
    name: string;
    hero_subtitle: string;
    status: string;
    to?: MaisonPage;
};

function RelatedCover({ src, alt }: { src: string | null; alt: string }) {
    const value = src ?? 'heritage-001-front';

    if (value in IMAGE_ASSETS) {
        return (
            <PlaceholderImage
                asset={value as ImageAssetName}
                ratio={null}
                alt={alt}
                captioned={false}
                className="absolute inset-0 h-full w-full"
            />
        );
    }

    if (
        value.startsWith('http://') ||
        value.startsWith('https://') ||
        value.startsWith('/')
    ) {
        return (
            <img
                src={value}
                alt={alt}
                className="absolute inset-0 h-full w-full object-cover"
            />
        );
    }

    return (
        <PlaceholderImage
            asset="heritage-001-front"
            ratio={null}
            alt={alt}
            captioned={false}
            className="absolute inset-0 h-full w-full"
        />
    );
}

export function ProductRelated({ related }: { related: RelatedEdition[] }) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const cards = [
        ...related,
        {
            cover_asset: 'atelier-workshop',
            slug: 'house',
            name: 'Het Huis',
            hero_subtitle: 'Meer van ons',
            status: 'Ontdek het volledige verhaal van Maison Anversa',
            to: 'house' as MaisonPage,
        },
    ];

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
                    {cards.map((card) => {
                        const body = (
                            <>
                                <div className="relative aspect-4/5 overflow-hidden">
                                    <RelatedCover
                                        src={card.cover_asset}
                                        alt={card.name}
                                    />
                                    <div
                                        aria-hidden="true"
                                        className="pointer-events-none absolute inset-0 bg-linear-to-t from-choc/55 to-transparent to-60%"
                                    />
                                </div>
                                <div className="px-6.5 py-7.5">
                                    <div className="font-sans text-[9px] tracking-[0.25em] text-gold uppercase">
                                        {card.name === 'Het Huis'
                                            ? t(card.name)
                                            : card.name}
                                    </div>
                                    <div className="my-2 font-serif text-[25px] text-cream">
                                        {t(card.hero_subtitle)}
                                    </div>
                                    <div className="text-xs leading-[1.6] text-sand">
                                        {t(card.status)}
                                    </div>
                                </div>
                            </>
                        );

                        return (
                            <Reveal
                                key={card.slug}
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
                                    <MaisonLink
                                        href={`${maisonUrl('products', locale)}/${card.slug}`}
                                        className="block transition-opacity hover:opacity-90"
                                    >
                                        {body}
                                    </MaisonLink>
                                )}
                            </Reveal>
                        );
                    })}
                </div>
            </Wrap>
        </Section>
    );
}
