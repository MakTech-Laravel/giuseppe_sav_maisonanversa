import { useTranslation } from 'react-i18next';
import { DressingItemMedia } from '@/components/maison/dressing/dressing-item-media';
import { MaisonLink } from '@/components/maison/maison-link';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import { useLocale } from '@/hooks/use-locale';
import { foundingProductUrl, maisonUrl } from '@/lib/maison-navigation';

type DressingItemCard = {
    name: string;
    slug: string;
    category: string;
    status: 'coming_soon' | 'available';
    image_url: string | null;
    image_key: string | null;
};

type DressingItemDetail = DressingItemCard & {
    description: string;
};

export default function DressingShow({
    item,
    related = [],
}: {
    item: DressingItemDetail;
    related?: DressingItemCard[];
}) {
    const { t } = useTranslation();
    const { openNewsletter } = useShellActions();
    const { locale } = useLocale();
    const statusLabel =
        item.status === 'available' ? t('Beschikbaar') : t('Binnenkort');

    return (
        <>
            <MaisonSeoHead title={item.name} description={item.description} />

            <PageHero
                eyebrow={item.category}
                title={item.name}
                subtitle={statusLabel}
            />

            <Section tone="cream">
                <Wrap>
                    <div className="grid gap-12 ma-lg:grid-cols-2">
                        <Reveal className="relative aspect-4/5 overflow-hidden bg-choc2">
                            <DressingItemMedia
                                imageUrl={item.image_url}
                                imageKey={item.image_key}
                                alt={item.name}
                                className="absolute inset-0 h-full w-full"
                            />
                        </Reveal>

                        <Reveal variant="left" className="flex flex-col">
                            <Eyebrow tone="gold2">{item.category}</Eyebrow>
                            <GoldRule />
                            <h2 className="mt-3 mb-5 font-serif text-[clamp(26px,3vw,38px)] leading-[1.2] font-medium">
                                {item.name}
                            </h2>
                            <span className="mb-5 inline-flex w-fit items-center rounded-full border border-gold/25 bg-gold/8 px-3 py-1 font-sans text-[9px] tracking-[0.2em] text-gold2 uppercase">
                                {statusLabel}
                            </span>
                            {item.description && (
                                <p className="mb-8 text-[15px] leading-[1.85] text-choc3">
                                    {item.description}
                                </p>
                            )}

                            {item.status === 'coming_soon' ? (
                                <MaisonButton
                                    variant="filled"
                                    onClick={openNewsletter}
                                    className="w-fit"
                                >
                                    {t('Schrijf in voor Heritage Letter')}
                                </MaisonButton>
                            ) : (
                                <MaisonButton
                                    as={MaisonLink}
                                    variant="choc"
                                    href={foundingProductUrl(locale)}
                                    className="w-fit"
                                >
                                    {t('Ontdek Heritage No.001 →')}
                                </MaisonButton>
                            )}

                            <MaisonLink
                                to="dressing"
                                className="mt-8 inline-flex min-h-11 w-fit items-center font-sans text-[9px] tracking-[0.22em] text-gold uppercase transition-colors hover:text-gold2"
                            >
                                {t('← Terug naar de Kleedkamer')}
                            </MaisonLink>
                        </Reveal>
                    </div>
                </Wrap>
            </Section>

            {related.length > 0 && (
                <Section tone="dark">
                    <Wrap>
                        <Reveal className="mb-12 text-center">
                            <Eyebrow>{t('Ook interessant')}</Eyebrow>
                            <GoldRule center className="mx-auto" />
                        </Reveal>

                        <div className="grid grid-cols-1 gap-0.5 md:grid-cols-3">
                            {related.map((entry) => (
                                <Reveal key={entry.slug}>
                                    <MaisonLink
                                        href={`${maisonUrl('dressing', locale)}/${entry.slug}`}
                                        className="group block h-full border border-gold/10 bg-white/3 transition-colors hover:border-gold/25 hover:bg-gold/5"
                                    >
                                        <div className="relative aspect-square overflow-hidden bg-choc2">
                                            <DressingItemMedia
                                                imageUrl={entry.image_url}
                                                imageKey={entry.image_key}
                                                alt={entry.name}
                                                className="absolute inset-0 h-full w-full transition-transform duration-500 group-hover:scale-[1.03]"
                                            />
                                        </div>
                                        <div className="px-4 pt-4 pb-6">
                                            <h3 className="mb-1.5 font-sans text-[9px] font-medium tracking-[0.2em] text-gold uppercase">
                                                {entry.name}
                                            </h3>
                                            <p className="text-[11px] tracking-[0.1em] text-sand uppercase">
                                                {entry.category}
                                            </p>
                                        </div>
                                    </MaisonLink>
                                </Reveal>
                            ))}
                        </div>
                    </Wrap>
                </Section>
            )}
        </>
    );
}
