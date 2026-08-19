import { useTranslation } from 'react-i18next';
import { JournalCard } from '@/components/maison/journal/journal-card';
import { JournalPagination } from '@/components/maison/journal/journal-pagination';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Section, Wrap } from '@/components/maison/ui/section';
import type { JournalPaginator } from '@/types/journal';

export default function Journal({ articles }: { articles: JournalPaginator }) {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow="Maison Anversa"
                title={
                    <>
                        {t('Het')} <em>Journal</em>
                    </>
                }
                subtitle={t(
                    'Verhalen over sport, cultuur, design en het leven. Vanuit de wereld van Maison Anversa.',
                )}
            />

            <Section tone="cream">
                <Wrap>
                    <div className="grid gap-10 ma-lg:grid-cols-3 md:grid-cols-2">
                        {articles.data.map((entry) => (
                            <JournalCard key={entry.slug} entry={entry} />
                        ))}
                    </div>

                    <JournalPagination articles={articles} />
                </Wrap>
            </Section>
        </>
    );
}
