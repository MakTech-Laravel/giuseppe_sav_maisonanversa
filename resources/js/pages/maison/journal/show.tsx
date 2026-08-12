import { useTranslation } from 'react-i18next';
import { JournalCard } from '@/components/maison/journal/journal-card';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { JournalArticle, JournalCard as JournalCardData } from '@/types/journal';

export default function JournalShow({
    article,
    related,
}: {
    article: JournalArticle;
    related: JournalCardData[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead
                page="journal"
                title={`${article.title} — Maison Anversa`}
                description={article.excerpt}
                articleSlug={article.slug}
                image={article.asset}
            />

            <PageHero
                className="[&_h1]:mx-auto [&_h1]:max-w-240 [&_h1]:text-[clamp(28px,4vw,46px)]"
                eyebrow={article.category}
                title={article.title}
                subtitle={article.meta}
            />

            <Section tone="cream">
                <Wrap>
                    <figure className="mx-auto mb-16 max-w-180">
                        <PlaceholderImage
                            asset={article.asset}
                            ratio={null}
                            alt={article.title}
                            captioned={false}
                            className="w-full bg-choc2 [&_img]:h-auto [&_img]:w-full [&_img]:object-contain"
                            loading="eager"
                        />
                    </figure>

                    <article className="mx-auto max-w-180">
                        <Reveal>
                            {article.body.map((paragraph) => (
                                <p
                                    key={paragraph.slice(0, 48)}
                                    className="mb-5 text-[17px] leading-[1.9] text-choc3"
                                >
                                    {paragraph}
                                </p>
                            ))}
                        </Reveal>

                        <MaisonLink
                            to="journal"
                            className="mt-12 inline-flex min-h-11 items-center font-sans text-[9px] tracking-[0.22em] text-gold uppercase transition-colors hover:text-gold2"
                        >
                            {t('← Terug naar het Journal')}
                        </MaisonLink>
                    </article>
                </Wrap>
            </Section>

            {related.length > 0 && (
                <Section tone="cream2">
                    <Wrap>
                        <Reveal className="mb-12 text-center">
                            <Eyebrow>{t('Gerelateerde verhalen')}</Eyebrow>
                            <GoldRule center className="mx-auto" />
                        </Reveal>

                        <div className="grid gap-10 ma-lg:grid-cols-3 md:grid-cols-2">
                            {related.map((entry) => (
                                <JournalCard key={entry.slug} entry={entry} />
                            ))}
                        </div>
                    </Wrap>
                </Section>
            )}
        </>
    );
}
