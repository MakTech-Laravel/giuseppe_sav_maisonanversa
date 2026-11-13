import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { EventCard } from '@/components/maison/community/community-events';
import { SessionCard } from '@/components/maison/community/sessions/session-card';
import {
    MemberEmptyState,
    MemberPageHeader,
    MemberPanel,
} from '@/components/member/member-ui';
import type { EventCard as EventCardData, SessionCard as SessionCardData } from '@/types/session';

type MemberPassportBadge = {
    id: string;
    label: string;
};

type MemberPassportPayload = {
    membership: {
        status: string;
        status_label: string;
        edition_number: string | null;
    };
    badges: MemberPassportBadge[];
    sessions: {
        upcoming: SessionCardData[];
        hosted_count: number;
        joined_count: number;
    };
    events: {
        upcoming: EventCardData[];
        past_count: number;
    };
};

export default function MemberLidpaspoort({
    passport,
}: {
    passport: MemberPassportPayload;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    return (
        <>
            <Head title={t('Member passport')} />
            <MemberPageHeader
                eyebrow={t('Membership')}
                title={t('Member passport')}
                description={t(
                    'Your digital passport in the house — membership, badges, sessions and events. This is separate from the physical Heritage Passport.',
                )}
            />

            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                <div className="space-y-6">
                    <MemberPanel>
                        <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                            {t('Membership')}
                        </p>
                        <p className="mt-3 font-serif text-[28px] text-cream">
                            {passport.membership.status_label}
                        </p>
                        {passport.membership.edition_number && (
                            <p className="mt-2 font-sans text-sm text-sand">
                                {t('Heritage Nº {{number}}', {
                                    number: passport.membership.edition_number,
                                })}
                            </p>
                        )}
                    </MemberPanel>

                    <MemberPanel>
                        <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                            {t('Badges')}
                        </p>
                        {passport.badges.length === 0 ? (
                            <p className="mt-4 font-sans text-sm text-sand">
                                {t(
                                    'No badges yet. Plan a session or RSVP to an event.',
                                )}
                            </p>
                        ) : (
                            <ul className="mt-4 flex flex-wrap gap-2">
                                {passport.badges.map((badge) => (
                                    <li
                                        key={badge.id}
                                        className="border border-gold/25 bg-choc px-3 py-2 font-sans text-[10px] tracking-[0.14em] text-gold2 uppercase"
                                    >
                                        {badge.label}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </MemberPanel>

                    <MemberPanel>
                        <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                            {t('Activity')}
                        </p>
                        <dl className="mt-4 grid gap-3 font-sans text-sm text-sand">
                            <div className="flex justify-between gap-4">
                                <dt>{t('Hosted sessions')}</dt>
                                <dd>{passport.sessions.hosted_count}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt>{t('Sessions joined')}</dt>
                                <dd>{passport.sessions.joined_count}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt>{t('Past events')}</dt>
                                <dd>{passport.events.past_count}</dd>
                            </div>
                        </dl>
                    </MemberPanel>
                </div>

                <div className="space-y-8">
                    <section>
                        <div className="mb-4 flex items-end justify-between gap-4">
                            <h2 className="font-serif text-[24px] text-cream">
                                {t('Upcoming sessions')}
                            </h2>
                            <Link
                                href={`/${locale}/community/sessions`}
                                className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase hover:text-gold2"
                            >
                                {t('All sessions')}
                            </Link>
                        </div>

                        {passport.sessions.upcoming.length === 0 ? (
                            <MemberEmptyState
                                title={t('No upcoming sessions')}
                                description={t(
                                    'Plan a session or join an open session in the Community.',
                                )}
                            />
                        ) : (
                            <div className="space-y-4">
                                {passport.sessions.upcoming.map((session) => (
                                    <SessionCard
                                        key={session.id}
                                        session={session}
                                    />
                                ))}
                            </div>
                        )}
                    </section>

                    <section>
                        <div className="mb-4 flex items-end justify-between gap-4">
                            <h2 className="font-serif text-[24px] text-cream">
                                {t('Upcoming events')}
                            </h2>
                            <Link
                                href={`/${locale}/community/events`}
                                className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase hover:text-gold2"
                            >
                                {t('All events')}
                            </Link>
                        </div>

                        {passport.events.upcoming.length === 0 ? (
                            <MemberEmptyState
                                title={t('No upcoming events')}
                                description={t(
                                    'Browse Maison Anversa exclusive events in the Community.',
                                )}
                            />
                        ) : (
                            <div className="space-y-4">
                                {passport.events.upcoming.map((event, index) => (
                                    <EventCard
                                        key={event.id}
                                        event={event}
                                        variant={
                                            ((index % 3) + 1) as 1 | 2 | 3
                                        }
                                    />
                                ))}
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
