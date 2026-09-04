import { Link, usePage } from '@inertiajs/react';
import { ExternalLink, MapPin, Phone } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import { PartnerBadge } from '@/components/maison/community/partner-badge';
import { SessionCard } from '@/components/maison/community/sessions/session-card';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';
import * as sessionRoutes from '@/routes/community/sessions';
import type { ClubCard, SessionCard as SessionCardData } from '@/types/session';

type ClubProfile = ClubCard & {
    slug: string;
    website: string | null;
    phone: string | null;
    upcoming_sessions: SessionCardData[];
    upcoming_sessions_count: number;
};

export default function ClubShow({ club }: { club: ClubProfile }) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    return (
        <>
            <MaisonSeoHead />

            <PageHero
                eyebrow={club.city}
                title={club.name}
                subtitle={club.address}
            />

            <CommunityTabs />

            <div className="bg-cream py-12">
                <Wrap className="space-y-10 px-6 md:px-10 lg:px-20">
                    <section className="border border-gold/15 bg-cream2 p-7">
                        <div className="flex flex-wrap items-start justify-between gap-6">
                            <div className="space-y-4">
                                {club.is_partner && <PartnerBadge />}

                                <div className="flex flex-wrap gap-2">
                                    {club.sports.map((sport) => (
                                        <span
                                            key={sport}
                                            className="border border-gold/20 px-2.5 py-1 font-sans text-[9px] tracking-[0.14em] text-choc uppercase"
                                        >
                                            {t(
                                                sport === 'padel'
                                                    ? 'Padel'
                                                    : 'Tennis',
                                            )}
                                        </span>
                                    ))}
                                </div>

                                <div className="space-y-2 font-sans text-sm text-stone">
                                    <p className="inline-flex items-center gap-2">
                                        <MapPin
                                            className="size-4 shrink-0"
                                            aria-hidden="true"
                                        />
                                        {club.address}
                                    </p>
                                    {club.phone && (
                                        <p className="inline-flex items-center gap-2">
                                            <Phone
                                                className="size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                            {club.phone}
                                        </p>
                                    )}
                                    {club.website && (
                                        <a
                                            href={club.website}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-2 text-gold2 hover:text-gold"
                                        >
                                            <ExternalLink
                                                className="size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                            {t('Website')}
                                        </a>
                                    )}
                                </div>
                            </div>

                            {club.image_url ? (
                                <img
                                    src={club.image_url}
                                    alt=""
                                    className="size-32 object-cover"
                                />
                            ) : (
                                <div
                                    aria-hidden="true"
                                    className="flex size-32 items-center justify-center bg-choc2 font-serif text-3xl text-gold"
                                >
                                    {club.name.slice(0, 1).toUpperCase()}
                                </div>
                            )}
                        </div>
                    </section>

                    <section>
                        <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                            <div>
                                <h2 className="font-serif text-[28px] font-medium text-choc">
                                    {t('Komende sessies')}
                                </h2>
                                <p className="mt-1 font-sans text-sm text-choc3">
                                    {club.upcoming_sessions_count === 0
                                        ? t(
                                              'Nog geen open sessies op deze locatie.',
                                          )
                                        : t(
                                              '{{count}} open sessie(s) op deze locatie.',
                                              {
                                                  count: club.upcoming_sessions_count,
                                              },
                                          )}
                                </p>
                            </div>

                            <Link
                                href={sessionRoutes.create.url(locale)}
                                className="inline-flex items-center border border-gold/30 px-5 py-3 font-sans text-[10px] tracking-[0.18em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10"
                            >
                                {t('Plan een sessie')}
                            </Link>
                        </div>

                        {club.upcoming_sessions.length === 0 ? (
                            <p className="border border-dashed border-gold/25 bg-cream2 px-6 py-14 text-center font-sans text-sm text-stone">
                                {t(
                                    'Nog geen open sessies op deze locatie. Plan de eerste.',
                                )}
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {club.upcoming_sessions.map((session) => (
                                    <SessionCard
                                        key={session.id}
                                        session={session}
                                    />
                                ))}
                            </div>
                        )}
                    </section>
                </Wrap>
            </div>
        </>
    );
}
