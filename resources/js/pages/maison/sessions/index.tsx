import { InfiniteScroll, Link, router, usePage } from '@inertiajs/react';
import { Plus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import { SessionCard } from '@/components/maison/community/sessions/session-card';
import { SessionTabs } from '@/components/maison/community/sessions/session-tabs';
import type { TabDefinition } from '@/components/maison/community/sessions/session-tabs';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';
import * as sessionRoutes from '@/routes/community/sessions';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types/admin';
import type {
    SessionCard as SessionCardData,
    SessionTab,
    SessionTabCounts,
} from '@/types/session';

type SessionsIndexProps = {
    sessions?: Paginated<SessionCardData>;
    tab: SessionTab;
    filters: { sport: string | null; city: string | null };
    counts: SessionTabCounts;
};

const SPORTS = ['padel', 'tennis'] as const;

export default function SessionsIndex({
    sessions,
    tab,
    filters,
    counts,
}: SessionsIndexProps) {
    const { t } = useTranslation();
    const page = usePage();
    const { locale } = page.props;
    const [city, setCity] = useState(filters.city ?? '');
    const firstRender = useRef(true);

    const indexUrl = sessionRoutes.index.url(locale);
    const hasScrollProp = page.scrollProps?.sessions != null;

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = window.setTimeout(() => {
            router.get(
                indexUrl,
                {
                    tab,
                    sport: filters.sport ?? undefined,
                    city: city || undefined,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => window.clearTimeout(timer);
        // `filters.sport` is applied by its own handler, not by this debounce.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [city]);

    function applySport(sport: string | null) {
        router.get(
            indexUrl,
            { tab, sport: sport ?? undefined, city: city || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const tabs: TabDefinition<SessionTab>[] = [
        { id: 'open', label: 'Open sessies', count: counts.open },
        { id: 'mine', label: 'Mijn sessies', count: counts.mine },
        { id: 'past', label: 'Afgelopen sessies', count: counts.past },
    ];

    const cards = (sessions?.data ?? []).map((session) => (
        <SessionCard key={session.id} session={session} />
    ));

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Community')}
                title={t('Sessies')}
                subtitle={t(
                    'Vind een open sessie of maak uw eigen sessie aan. Maison Anversa brengt spelers samen; boeken en betalen regelt u zelf met de club.',
                )}
            />

            <CommunityTabs />

            <div className="min-h-150 bg-cream pb-20">
                <Wrap className="px-6 md:px-10 lg:px-20">
                    <div className="flex flex-wrap items-end justify-between gap-4 py-10">
                        <div>
                            <h2 className="font-serif text-[28px] font-medium text-choc">
                                {t('Sessies')}
                            </h2>
                            <p className="mt-1 font-sans text-sm text-choc3">
                                {t(
                                    'Vind een open sessie of maak er zelf een aan.',
                                )}
                            </p>
                        </div>

                        <Link
                            href={sessionRoutes.create.url(locale)}
                            className="inline-flex items-center gap-2 bg-choc px-6 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2"
                        >
                            <Plus className="size-3.5" aria-hidden="true" />
                            {t('Plan een sessie')}
                        </Link>
                    </div>

                    <SessionTabs
                        tabs={tabs}
                        activeTab={tab}
                        ariaLabel={t('Sessies')}
                        hrefFor={(next) =>
                            sessionRoutes.index.url(locale, {
                                query: { tab: next },
                            })
                        }
                    />

                    {tab === 'open' && (
                        <div className="grid gap-3 py-6 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <div className="relative">
                                <Search
                                    className="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-stone"
                                    aria-hidden="true"
                                />
                                <input
                                    type="search"
                                    value={city}
                                    onChange={(event) =>
                                        setCity(event.target.value)
                                    }
                                    placeholder={t('Filter op stad...')}
                                    aria-label={t('Filter op stad...')}
                                    className="w-full border border-gold/20 bg-cream2 py-3 pr-4 pl-11 font-serif text-base text-choc outline-none transition-colors focus:border-gold2"
                                />
                            </div>

                            <div className="flex gap-2">
                                <SportFilter
                                    active={filters.sport === null}
                                    onClick={() => applySport(null)}
                                    label={t('Alle sporten')}
                                />
                                {SPORTS.map((sport) => (
                                    <SportFilter
                                        key={sport}
                                        active={filters.sport === sport}
                                        onClick={() => applySport(sport)}
                                        label={t(
                                            sport === 'padel'
                                                ? 'Padel'
                                                : 'Tennis',
                                        )}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {cards.length === 0 ? (
                        <EmptyState tab={tab} />
                    ) : hasScrollProp ? (
                        <InfiniteScroll
                            data="sessions"
                            manual
                            className="mt-4 space-y-4"
                            next={({ loading, fetch, hasNext }) =>
                                hasNext ? (
                                    <div className="flex justify-center pt-10">
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
                        <div className="mt-4 space-y-4">{cards}</div>
                    )}
                </Wrap>
            </div>
        </>
    );
}

function SportFilter({
    active,
    onClick,
    label,
}: {
    active: boolean;
    onClick: () => void;
    label: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            className={cn(
                'cursor-pointer border px-5 py-3 font-sans text-[10px] font-medium tracking-[0.16em] uppercase transition-colors',
                active
                    ? 'border-choc bg-choc text-cream'
                    : 'border-gold/25 bg-cream2 text-choc hover:border-gold',
            )}
        >
            {label}
        </button>
    );
}

function EmptyState({ tab }: { tab: SessionTab }) {
    const { t } = useTranslation();

    const copy: Record<SessionTab, string> = {
        open: 'Nog geen open sessies. Plan de eerste.',
        mine: 'U neemt nog niet deel aan een sessie.',
        past: 'U heeft nog geen afgelopen sessies.',
    };

    return (
        <p className="border border-dashed border-gold/25 bg-cream2 px-6 py-14 text-center font-sans text-sm text-stone">
            {t(copy[tab])}
        </p>
    );
}
