import { InfiniteScroll, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { EventCard } from '@/components/maison/community/community-events';
import { SessionBreadcrumb } from '@/components/maison/community/sessions/session-breadcrumb';
import { SessionTabs } from '@/components/maison/community/sessions/session-tabs';
import type { TabDefinition } from '@/components/maison/community/sessions/session-tabs';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';
import * as eventRoutes from '@/routes/community/events';
import * as maisonRoutes from '@/routes/maison';
import type { Paginated } from '@/types/admin';
import type { EventCard as EventCardData, SessionTab } from '@/types/session';

type EventsIndexProps = {
    events?: Paginated<EventCardData>;
    tab: SessionTab;
    counts: Record<SessionTab, number>;
};

export default function EventsIndex({ events, tab, counts }: EventsIndexProps) {
    const { t } = useTranslation();
    const page = usePage();
    const { locale } = page.props;
    const hasScrollProp = page.scrollProps?.events != null;

    const tabs: TabDefinition<SessionTab>[] = [
        { id: 'open', label: 'Open events', count: counts.open },
        { id: 'mine', label: 'Mijn events', count: counts.mine },
        { id: 'past', label: 'Afgelopen events', count: counts.past },
    ];

    const cards = (events?.data ?? []).map((event, index) => (
        <EventCard
            key={event.id}
            event={event}
            variant={((index % 3) + 1) as 1 | 2 | 3}
        />
    ));

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Community')}
                title={t('Exclusieve Events')}
                subtitle={t(
                    'Alleen zichtbaar voor ingelogde leden. Aanmelden is gratis tenzij anders vermeld.',
                )}
            />

            <SessionBreadcrumb
                crumbs={[
                    {
                        label: 'Community',
                        href: maisonRoutes.community.url(locale),
                    },
                    { label: 'Exclusieve Events' },
                ]}
            />

            <div className="min-h-150 bg-cream pb-20">
                <Wrap className="px-6 md:px-10 lg:px-20">
                    <div className="py-10">
                        <h2 className="font-serif text-[28px] font-medium text-choc">
                            {t('Exclusieve Events')}
                        </h2>
                        <p className="mt-1 font-sans text-sm text-choc3">
                            {t('Georganiseerd door Maison Anversa.')}
                        </p>
                    </div>

                    <SessionTabs
                        tabs={tabs}
                        activeTab={tab}
                        ariaLabel={t('Exclusieve Events')}
                        hrefFor={(next) =>
                            eventRoutes.index.url(locale, {
                                query: { tab: next },
                            })
                        }
                    />

                    {cards.length === 0 ? (
                        <EmptyState tab={tab} />
                    ) : hasScrollProp ? (
                        <InfiniteScroll
                            data="events"
                            manual
                            className="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3"
                            next={({ loading, fetch, hasNext }) =>
                                hasNext ? (
                                    <div className="col-span-full flex justify-center pt-6">
                                        <button
                                            type="button"
                                            onClick={fetch}
                                            disabled={loading}
                                            className="cursor-pointer border border-gold/30 px-8 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10 disabled:opacity-50"
                                        >
                                            {loading
                                                ? t('Laden...')
                                                : t('Meer laden')}
                                        </button>
                                    </div>
                                ) : null
                            }
                        >
                            {cards}
                        </InfiniteScroll>
                    ) : (
                        <div className="mt-8 grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                            {cards}
                        </div>
                    )}
                </Wrap>
            </div>
        </>
    );
}

function EmptyState({ tab }: { tab: SessionTab }) {
    const { t } = useTranslation();

    const copy: Record<SessionTab, string> = {
        open: 'Nog geen events gepland.',
        mine: 'U bent nog niet aangemeld voor een event.',
        past: 'U heeft nog geen afgelopen events.',
    };

    return (
        <p className="mt-8 border border-dashed border-gold/25 bg-cream2 px-6 py-14 text-center font-sans text-sm text-stone">
            {t(copy[tab])}
        </p>
    );
}
