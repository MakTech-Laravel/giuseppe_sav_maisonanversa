import { router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Monogram } from '@/components/maison/ui/monogram';
import { formatSessionDate, formatSessionTime } from '@/lib/session-format';
import { cn } from '@/lib/utils';
import type { EventCard as EventCardData } from '@/types/session';

const EVENT_IMAGE_CLASSES = {
    1: 'bg-linear-to-br from-[#1F3D2E] to-[#0F2218]',
    2: 'bg-linear-to-br from-[#352722] to-[#291c18]',
    3: 'bg-linear-to-br from-[#1A1F2E] to-[#0C1018]',
} as const;

type EventCardProps = {
    event: EventCardData;
    variant: 1 | 2 | 3;
};

export function EventCard({ event, variant }: EventCardProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    function handleJoin() {
        router.post(
            `/${locale}/community/events/${event.id}/rsvp`,
            {},
            { preserveScroll: true },
        );
    }

    function handleCancel() {
        router.delete(`/${locale}/community/events/${event.id}/rsvp`, {
            preserveScroll: true,
        });
    }

    return (
        <article className="overflow-hidden border border-gold/10 bg-choc2 transition-colors hover:border-gold/25">
            <div
                className={cn(
                    'relative flex h-40 items-center justify-center overflow-hidden',
                    !event.thumbnail_url && EVENT_IMAGE_CLASSES[variant],
                )}
            >
                {event.thumbnail_url ? (
                    <img
                        src={event.thumbnail_url}
                        alt={event.title}
                        className="absolute inset-0 size-full object-cover"
                    />
                ) : (
                    <span className="font-serif text-lg tracking-[0.2em] text-gold/8 uppercase">
                        Maison Anversa
                    </span>
                )}
                <span className="absolute top-3.5 left-4 border border-gold/30 bg-choc/80 px-2.5 py-1 font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                    {t('Alleen leden')}
                </span>
            </div>

            <div className="px-5 pt-5 pb-6">
                <div className="mb-2 font-sans text-[9px] tracking-[0.2em] text-gold uppercase">
                    {formatSessionDate(event.starts_at, locale)} ·{' '}
                    {formatSessionTime(event.starts_at, locale)}
                </div>
                <div className="mb-1.5 font-serif text-xl font-medium text-cream">
                    {event.title}
                </div>
                <div className="mb-3.5 font-sans text-[9px] tracking-[0.1em] text-stone">
                    {event.location}
                    {event.capacity != null
                        ? ` · ${event.rsvp_count}/${event.capacity}`
                        : ''}
                </div>

                <div className="mb-4 flex items-center gap-2.5">
                    <div className="flex">
                        {event.attendees.map((attendee, index) => (
                            <Monogram
                                key={attendee.id}
                                initials={attendee.initials}
                                title={attendee.name}
                                className={cn(
                                    'size-7 border-2 border-choc2 text-[10px]',
                                    index > 0 && '-ml-1.5',
                                )}
                            />
                        ))}
                    </div>
                    <div className="font-sans text-[10px] tracking-[0.1em] text-stone">
                        {event.rsvp_count === 0
                            ? t('Nog niemand heeft geboekt.')
                            : event.rsvp_count === 1
                              ? t('1 lid gaat')
                              : t('{{count}} leden gaan', {
                                    count: event.rsvp_count,
                                })}
                    </div>
                </div>

                <EventAction
                    event={event}
                    onJoin={handleJoin}
                    onCancel={handleCancel}
                />
            </div>
        </article>
    );
}

function EventAction({
    event,
    onJoin,
    onCancel,
}: {
    event: EventCardData;
    onJoin: () => void;
    onCancel: () => void;
}) {
    const { t } = useTranslation();

    if (event.is_past) {
        return (
            <span className="block w-full border border-gold/15 bg-gold/5 px-3 py-3 text-center font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                {t('Evenement afgelopen')}
            </span>
        );
    }

    if (event.can_leave) {
        return (
            <button
                type="button"
                onClick={onCancel}
                className="w-full cursor-pointer border border-gold/25 bg-gold/25 px-3 py-3 font-sans text-[10px] font-medium tracking-[0.2em] text-gold uppercase transition-colors hover:bg-gold/35"
            >
                {t('Aanmelding annuleren')}
            </button>
        );
    }

    return (
        <button
            type="button"
            disabled={!event.can_join}
            onClick={onJoin}
            className="w-full cursor-pointer border border-gold/25 bg-gold/10 px-3 py-3 font-sans text-[10px] font-medium tracking-[0.2em] text-gold uppercase transition-colors hover:bg-gold/20 disabled:cursor-default disabled:opacity-50"
        >
            {event.is_full ? t('Volgeboekt') : t('Bevestig deelname →')}
        </button>
    );
}
