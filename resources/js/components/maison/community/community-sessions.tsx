import { useState, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import {
    SESSION_CARDS,
    SESSION_LEVELS,
    SESSION_LOCATIONS,
    SESSION_PLAYERS_WANTED,
    type SessionCardData,
} from '@/components/maison/community/community-data';
import { Monogram } from '@/components/maison/ui/monogram';
import { Wrap } from '@/components/maison/ui/section';
import { cn } from '@/lib/utils';

type CommunitySessionsProps = {
    onJoin: () => void;
};

export function CommunitySessions({ onJoin }: CommunitySessionsProps) {
    const { t } = useTranslation();
    const [sessions, setSessions] = useState(SESSION_CARDS);
    const [created, setCreated] = useState(false);

    function handleJoin(sessionId: string) {
        setSessions((current) =>
            current.map((session) => {
                if (session.id !== sessionId || session.joined) {
                    return session;
                }

                const spotsMatch = session.spots.match(/^(\d+)/);
                const openSpots = spotsMatch
                    ? Number.parseInt(spotsMatch[1], 10)
                    : 0;
                const nextSpots = openSpots - 1;

                return {
                    ...session,
                    joined: true,
                    spots:
                        nextSpots <= 0
                            ? 'Vol'
                            : nextSpots === 1
                              ? '1 plek vrij'
                              : `${nextSpots} plekken vrij`,
                    players: [...session.players, 'YS'],
                    emptySlots: Math.max(0, session.emptySlots - 1),
                };
            }),
        );

        onJoin();
    }

    function handleCreateSession() {
        setCreated(true);
    }

    return (
        <Wrap className="grid items-start gap-12 px-6 py-12 md:px-10 lg:grid-cols-[1fr_380px] lg:px-20">
            <div>
                <h2 className="mb-6 font-serif text-[28px] font-medium text-choc">
                    {t('Open Sessies')}
                </h2>

                {sessions.map((session) => (
                    <SessionCard
                        key={session.id}
                        session={session}
                        onJoin={() => handleJoin(session.id)}
                    />
                ))}
            </div>

            <div className="border border-gold/20 bg-cream2 p-7">
                <div className="mb-5 font-serif text-[22px] font-medium text-choc">
                    {t('Plan een nieuwe sessie')}
                </div>

                {!created ? (
                    <SessionPlannerForm onCreate={handleCreateSession} />
                ) : (
                    <div className="text-center">
                        <div className="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-gold/15 text-xl text-gold2">
                            ✓
                        </div>
                        <h3 className="mb-2 font-serif text-xl text-choc">
                            {t('Sessie aangemaakt.')}
                        </h3>
                        <p className="text-sm text-choc3">
                            {t('Andere leden kunnen nu aansluiten.')}
                        </p>
                    </div>
                )}
            </div>
        </Wrap>
    );
}

type SessionCardProps = {
    session: SessionCardData & { joined?: boolean };
    onJoin: () => void;
};

function SessionCard({ session, onJoin }: SessionCardProps) {
    const { t } = useTranslation();
    const full = session.spots === 'Vol';

    return (
        <article className="mb-4 border border-gold/15 bg-cream2 p-7">
            <div className="mb-3 flex items-start justify-between">
                <div className="font-serif text-[22px] font-medium text-choc">
                    {t(session.title)}
                </div>
                <div
                    className={cn(
                        'border border-gold/20 bg-gold/8 px-3 py-1 font-sans text-[10px] tracking-[0.15em] uppercase',
                        full ? 'text-stone' : 'text-gold2',
                    )}
                >
                    {t(session.spots)}
                </div>
            </div>

            <div className="mb-4 flex flex-wrap gap-6">
                {session.meta.map((item) => (
                    <div
                        key={item}
                        className="font-sans text-[10px] tracking-[0.1em] text-stone"
                    >
                        {t(item)}
                    </div>
                ))}
            </div>

            <div className="mb-4 flex items-center gap-2">
                {session.players.map((player) => (
                    <Monogram key={player} initials={player} size="md" />
                ))}
                {Array.from({ length: session.emptySlots }).map((_, index) => (
                    <Monogram
                        key={`empty-${index}`}
                        initials="+"
                        size="md"
                        className="border-dashed bg-gold/4 text-gold/30"
                    />
                ))}
            </div>

            <button
                type="button"
                disabled={session.joined || full}
                onClick={onJoin}
                className={cn(
                    'cursor-pointer border-none px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] uppercase transition-colors disabled:cursor-default',
                    session.joined
                        ? 'bg-gold2 text-choc'
                        : 'bg-choc text-cream hover:bg-gold2',
                )}
            >
                {session.joined
                    ? t('✓ Aangemeld')
                    : t('Sluit aan bij deze sessie')}
            </button>
        </article>
    );
}

type SessionPlannerFormProps = {
    onCreate: () => void;
};

function SessionPlannerForm({ onCreate }: SessionPlannerFormProps) {
    const { t } = useTranslation();

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                onCreate();
            }}
            className="space-y-4"
        >
            <Field label={t('Club Corner locatie')}>
                <select className={fieldClassName}>
                    {SESSION_LOCATIONS.map((location) => (
                        <option key={location}>{t(location)}</option>
                    ))}
                </select>
            </Field>

            <Field label={t('Datum')}>
                <input
                    type="date"
                    defaultValue="2027-02-08"
                    className={fieldClassName}
                />
            </Field>

            <Field label={t('Tijdstip')}>
                <input
                    type="time"
                    defaultValue="10:00"
                    className={fieldClassName}
                />
            </Field>

            <Field label={t('Niveau')}>
                <select className={fieldClassName}>
                    {SESSION_LEVELS.map((level) => (
                        <option key={level}>{t(level)}</option>
                    ))}
                </select>
            </Field>

            <Field label={t('Extra spelers gezocht')}>
                <select className={fieldClassName}>
                    {SESSION_PLAYERS_WANTED.map((option) => (
                        <option key={option}>{t(option)}</option>
                    ))}
                </select>
            </Field>

            <Field label={t('Notitie (optioneel)')}>
                <input
                    type="text"
                    placeholder={t('Bijv. niveau, taal, bijzonderheden...')}
                    className={fieldClassName}
                />
            </Field>

            <button
                type="submit"
                className="w-full cursor-pointer bg-choc px-6 py-4 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2"
            >
                {t('Sessie Aanmaken')}
            </button>
        </form>
    );
}

const fieldClassName =
    'w-full border border-gold/20 bg-cream px-4 py-3 font-serif text-base text-choc outline-none transition-colors focus:border-gold2';

function Field({ label, children }: { label: string; children: ReactNode }) {
    return (
        <label className="flex flex-col gap-1.5">
            <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                {label}
            </span>
            {children}
        </label>
    );
}
