import { InfiniteScroll, Link, router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import { PartnerBadge } from '@/components/maison/community/partner-badge';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';
import { cn } from '@/lib/utils';
import * as clubRoutes from '@/routes/community/clubs';
import type { Paginated } from '@/types/admin';
import type { ClubCard } from '@/types/session';

type ClubDirectoryCard = ClubCard & {
    slug: string;
    website: string | null;
    phone: string | null;
};

type ClubsIndexProps = {
    clubs?: Paginated<ClubDirectoryCard>;
    filters: {
        search: string | null;
        city: string | null;
        sport: string | null;
    };
    cities: string[];
};

const SPORTS = ['padel', 'tennis'] as const;

export default function ClubsIndex({ clubs, filters, cities }: ClubsIndexProps) {
    const { t } = useTranslation();
    const page = usePage();
    const { locale } = page.props;
    const [search, setSearch] = useState(filters.search ?? '');
    const [city, setCity] = useState(filters.city ?? '');
    const firstSearchRender = useRef(true);
    const firstCityRender = useRef(true);
    const indexUrl = clubRoutes.index.url(locale);
    const hasScrollProp = page.scrollProps?.clubs != null;

    useEffect(() => {
        if (firstSearchRender.current) {
            firstSearchRender.current = false;

            return;
        }

        const timer = window.setTimeout(() => {
            router.get(
                indexUrl,
                {
                    search: search || undefined,
                    city: city || undefined,
                    sport: filters.sport ?? undefined,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    useEffect(() => {
        if (firstCityRender.current) {
            firstCityRender.current = false;

            return;
        }

        router.get(
            indexUrl,
            {
                search: search || undefined,
                city: city || undefined,
                sport: filters.sport ?? undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [city]);

    function applySport(sport: string | null) {
        router.get(
            indexUrl,
            {
                search: search || undefined,
                city: city || undefined,
                sport: sport ?? undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const cards = (clubs?.data ?? []).map((club) => (
        <ClubDirectoryCard key={club.id} club={club} />
    ));

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={t('Community')}
                title={t('Clubs')}
                subtitle={t(
                    'Ontdek padel- en tennisclubs waar leden sessies plannen. Partnerclubs dragen het officiële Maison Anversa-partnerschap.',
                )}
            />

            <CommunityTabs />

            <div className="min-h-150 bg-cream pb-20">
                <Wrap className="px-6 md:px-10 lg:px-20">
                    <div className="py-10">
                        <h2 className="font-serif text-[28px] font-medium text-choc">
                            {t('Clubs')}
                        </h2>
                        <p className="mt-1 font-sans text-sm text-choc3">
                            {t(
                                'Zoek een club en bekijk komende sessies op die locatie.',
                            )}
                        </p>
                    </div>

                    <div className="grid gap-3 pb-6 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                        <div className="relative">
                            <Search
                                className="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-stone"
                                aria-hidden="true"
                            />
                            <input
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder={t('Zoek op clubnaam...')}
                                aria-label={t('Zoek op clubnaam...')}
                                className="w-full border border-gold/20 bg-cream2 py-3 pr-4 pl-11 font-serif text-base text-choc transition-colors outline-none focus:border-gold2"
                            />
                        </div>

                        <select
                            value={city}
                            onChange={(event) => setCity(event.target.value)}
                            aria-label={t('Filter op stad...')}
                            className="border border-gold/20 bg-cream2 px-4 py-3 font-sans text-sm text-choc outline-none focus:border-gold2"
                        >
                            <option value="">{t('Alle steden')}</option>
                            {cities.map((option) => (
                                <option key={option} value={option}>
                                    {option}
                                </option>
                            ))}
                        </select>

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
                                        sport === 'padel' ? 'Padel' : 'Tennis',
                                    )}
                                />
                            ))}
                        </div>
                    </div>

                    {cards.length === 0 ? (
                        <p className="border border-dashed border-gold/25 bg-cream2 px-6 py-14 text-center font-sans text-sm text-stone">
                            {t('Geen clubs gevonden voor deze filters.')}
                        </p>
                    ) : hasScrollProp ? (
                        <InfiniteScroll
                            data="clubs"
                            manual
                            className="grid gap-4 md:grid-cols-2"
                            next={({ loading, fetch, hasNext }) =>
                                hasNext ? (
                                    <div className="col-span-full flex justify-center pt-10">
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
                        <div className="grid gap-4 md:grid-cols-2">{cards}</div>
                    )}
                </Wrap>
            </div>
        </>
    );
}

function ClubDirectoryCard({ club }: { club: ClubDirectoryCard }) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    return (
        <Link
            href={clubRoutes.show({ locale, club: club.id }).url}
            className="flex flex-col gap-4 border border-gold/15 bg-cream2 p-6 transition-colors hover:border-gold/35"
        >
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                        {club.city}
                    </p>
                    <h3 className="mt-1 font-serif text-[22px] font-medium text-choc">
                        {club.name}
                    </h3>
                </div>
                {club.image_url ? (
                    <img
                        src={club.image_url}
                        alt=""
                        className="size-16 shrink-0 object-cover"
                    />
                ) : (
                    <div
                        aria-hidden="true"
                        className="flex size-16 shrink-0 items-center justify-center bg-choc2 font-serif text-lg text-gold"
                    >
                        {club.name.slice(0, 1).toUpperCase()}
                    </div>
                )}
            </div>

            {club.is_partner && <PartnerBadge />}

            <p className="font-sans text-sm text-stone">{club.address}</p>

            <div className="flex flex-wrap gap-2">
                {club.sports.map((sport) => (
                    <span
                        key={sport}
                        className="border border-gold/20 px-2.5 py-1 font-sans text-[9px] tracking-[0.14em] text-choc uppercase"
                    >
                        {t(sport === 'padel' ? 'Padel' : 'Tennis')}
                    </span>
                ))}
            </div>
        </Link>
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
