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
import type { ProductSection } from '@/types/product';

type RelatedEdition = {
    cover_asset: string | null;
    slug: string;
    name: string;
    hero_subtitle: string;
    status: string;
    to?: MaisonPage;
};

type RelatedCard = {
    key: string;
    cover: string | null;
    title: string;
    imageAlt: string;
    subtitle: string;
    note: string;
    to?: MaisonPage;
    href?: string;
};

const STATUS_LABELS: Record<string, string> = {
    active: 'Beschikbaar',
    coming_soon: 'Binnenkort',
    archived: 'Uitverkocht',
};

const RELATED_PRODUCT_TITLE_MAX = 40;

function truncateWithEllipsis(text: string, maxLength: number): string {
    if (text.length <= maxLength) {
        return text;
    }

    return `${text.slice(0, maxLength).trimEnd()}...`;
}

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

export function ProductRelated({
    related,
    section,
}: {
    related: RelatedEdition[];
    section?: ProductSection;
}) {
    const { t } = useTranslation();
    const { locale } = useLocale();

    const cards: RelatedCard[] = related.map((item) => ({
        key: item.slug,
        cover: item.cover_asset,
        title: truncateWithEllipsis(item.name, RELATED_PRODUCT_TITLE_MAX),
        imageAlt: item.name,
        subtitle: item.hero_subtitle,
        note: t(STATUS_LABELS[item.status] ?? item.status),
        href: `${maisonUrl('products', locale)}/${item.slug}`,
    }));

    if (section?.include_house_card) {
        cards.push({
            key: 'house',
            cover: 'atelier-workshop',
            title: t('Het Huis'),
            imageAlt: t('Het Huis'),
            subtitle: t('Meer van ons'),
            note: t('Ontdek het volledige verhaal van Maison Anversa'),
            to: 'house',
        });
    }

    if (cards.length === 0) {
        return null;
    }

    return (
        <Section tone="dark">
            <Wrap>
                <Reveal className="mb-13 text-center">
                    {section?.eyebrow ? (
                        <Eyebrow>{section.eyebrow}</Eyebrow>
                    ) : null}
                    <GoldRule center className="mx-auto" />
                    {section?.heading ? (
                        <h2 className="font-serif text-[clamp(28px,3.5vw,42px)] font-medium tracking-[0.04em] text-cream uppercase">
                            {section.heading}
                        </h2>
                    ) : null}
                </Reveal>

                <div className="grid gap-6 md:grid-cols-3">
                    {cards.map((card) => {
                        const body = (
                            <>
                                <div className="relative aspect-4/5 overflow-hidden">
                                    <RelatedCover
                                        src={card.cover}
                                        alt={card.imageAlt}
                                    />
                                    <div
                                        aria-hidden="true"
                                        className="pointer-events-none absolute inset-0 bg-linear-to-t from-choc/55 to-transparent to-60%"
                                    />
                                </div>
                                <div className="px-6.5 py-7.5">
                                    <div className="font-sans text-[9px] tracking-[0.25em] text-gold uppercase">
                                        {card.title}
                                    </div>
                                    <div className="my-2 font-serif text-[25px] text-cream">
                                        {card.subtitle}
                                    </div>
                                    <div className="text-xs leading-[1.6] text-sand">
                                        {card.note}
                                    </div>
                                </div>
                            </>
                        );

                        return (
                            <Reveal
                                key={card.key}
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
                                        href={card.href ?? '#'}
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
