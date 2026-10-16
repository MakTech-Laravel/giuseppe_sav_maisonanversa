import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { formatSessionDate, formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import type { SessionCard } from '@/types/session';

type ChatSessionListProps = {
    sessions: SessionCard[];
    hrefFor: (session: SessionCard) => string;
    activeSessionId?: string | null;
    unreadCounts?: Record<string, number>;
    className?: string;
};

export function ChatSessionList({
    sessions,
    hrefFor,
    activeSessionId = null,
    unreadCounts = {},
    className,
}: ChatSessionListProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    if (sessions.length === 0) {
        return (
            <div
                className={cn(
                    'flex flex-1 flex-col items-center justify-center gap-2 px-6 py-14 text-center',
                    className,
                )}
            >
                <p className="font-serif text-lg text-cream">
                    {t('Nog geen chats')}
                </p>
                <p className="max-w-xs font-sans text-[12px] text-sand">
                    {t(
                        'Zodra u een sessie host of goedgekeurd bent, verschijnt die hier.',
                    )}
                </p>
            </div>
        );
    }

    return (
        <ul
            className={cn('flex flex-1 flex-col overflow-y-auto', className)}
            aria-label={t('Sessies')}
        >
            {sessions.map((session) => {
                const active = activeSessionId === session.id;
                const clubName = session.club?.name ?? t('Sessie');
                const unread = unreadCounts[session.id] ?? 0;

                return (
                    <li key={session.id} className="border-b border-gold/20">
                        <Link
                            href={hrefFor(session)}
                            className={cn(
                                'block px-4 py-2.5 transition-colors',
                                active
                                    ? 'bg-choc2'
                                    : 'bg-transparent hover:bg-choc2',
                            )}
                            aria-current={active ? 'page' : undefined}
                        >
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="min-w-0 flex-1 truncate font-serif text-[15px] font-medium text-cream">
                                    {clubName}
                                </span>
                                {unread > 0 ? (
                                    <span className="inline-flex h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-gold px-1 text-[9px] leading-none font-semibold text-choc tabular-nums">
                                        {unread > 99 ? '99+' : unread}
                                    </span>
                                ) : null}
                                {session.is_cancelled && (
                                    <StatusPill label={t('Geannuleerd')} />
                                )}
                                {!session.is_cancelled &&
                                    session.lifecycle === 'finished' && (
                                        <StatusPill
                                            label={t(
                                                session.lifecycle_label ??
                                                    'Afgelopen',
                                            )}
                                        />
                                    )}
                            </div>
                            <p className="mt-0.5 font-sans text-[10px] tracking-[0.12em] text-stone uppercase">
                                {t(session.sport_label)}
                                {session.club?.city
                                    ? ` · ${session.club.city}`
                                    : ''}
                            </p>
                            <p className="mt-1 font-sans text-[11px] tracking-[0.06em] text-sand">
                                {formatSessionDate(session.starts_at, locale)}
                                {' · '}
                                {formatSessionTime(session.starts_at, locale)}
                            </p>
                        </Link>
                    </li>
                );
            })}
        </ul>
    );
}

function StatusPill({ label }: { label: string }) {
    return (
        <span className="border border-gold/25 bg-gold/10 px-2 py-0.5 font-sans text-[9px] tracking-[0.14em] text-gold uppercase">
            {label}
        </span>
    );
}
