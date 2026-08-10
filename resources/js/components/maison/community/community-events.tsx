import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    EVENT_CARDS,
    type EventCardData,
} from '@/components/maison/community/community-data';
import { Monogram } from '@/components/maison/ui/monogram';
import { Wrap } from '@/components/maison/ui/section';
import { cn } from '@/lib/utils';

type CommunityEventsProps = {
    onRsvp: () => void;
};

const EVENT_IMAGE_CLASSES = {
    1: 'bg-linear-to-br from-[#1F3D2E] to-[#0F2218]',
    2: 'bg-linear-to-br from-[#352722] to-[#291c18]',
    3: 'bg-linear-to-br from-[#1A1F2E] to-[#0C1018]',
} as const;

export function CommunityEvents({ onRsvp }: CommunityEventsProps) {
    const { t } = useTranslation();
    const [events, setEvents] = useState(
        EVENT_CARDS.map((event) => ({ ...event, joined: false })),
    );

    function handleJoin(eventId: string) {
        setEvents((current) =>
            current.map((event) =>
                event.id === eventId ? { ...event, joined: true } : event,
            ),
        );
        onRsvp();
    }

    return (
        <Wrap className="px-6 py-12 md:px-10 lg:px-20">
            <h2 className="mb-2 font-serif text-[28px] font-medium text-choc">
                {t('Exclusieve Events')}
            </h2>
            <p className="mb-10 max-w-140 text-base text-choc3">
                {t(
                    'Alleen zichtbaar voor Founding Circle leden en Club Corner partners. Aanmelden is gratis tenzij anders vermeld.',
                )}
            </p>

            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                {events.map((event) => (
                    <EventCard
                        key={event.id}
                        event={event}
                        onJoin={() => handleJoin(event.id)}
                    />
                ))}
            </div>
        </Wrap>
    );
}

type EventCardProps = {
    event: EventCardData & { joined?: boolean };
    onJoin: () => void;
};

function EventCard({ event, onJoin }: EventCardProps) {
    const { t } = useTranslation();

    return (
        <article className="overflow-hidden border border-gold/10 bg-choc2 transition-colors hover:border-gold/25">
            <div
                className={cn(
                    'relative flex h-40 items-center justify-center',
                    EVENT_IMAGE_CLASSES[event.imageVariant],
                )}
            >
                <span className="font-serif text-lg tracking-[0.2em] text-gold/8 uppercase">
                    {event.imageLabel}
                </span>
                <span className="absolute top-3.5 left-4 border border-gold/30 bg-choc/80 px-2.5 py-1 font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                    {t('Founding Circle Only')}
                </span>
            </div>

            <div className="px-5 pt-5 pb-6">
                <div className="mb-2 font-sans text-[9px] tracking-[0.2em] text-gold uppercase">
                    {t(event.date)}
                </div>
                <div className="mb-1.5 font-serif text-xl font-medium text-cream">
                    {t(event.title)}
                </div>
                <div className="mb-3.5 font-sans text-[9px] tracking-[0.1em] text-stone">
                    {t(event.location)}
                </div>

                <div className="mb-4 flex items-center gap-2.5">
                    <div className="flex">
                        {event.attendees.map((initials, index) => (
                            <Monogram
                                key={`${initials}-${index}`}
                                initials={initials}
                                className={cn(
                                    'size-7 border-2 border-choc2 text-[10px]',
                                    index > 0 && '-ml-1.5',
                                )}
                            />
                        ))}
                        {event.attendees.length > 1 && (
                            <Monogram
                                initials="+"
                                className="-ml-1.5 size-7 border-2 border-choc2 text-[10px]"
                            />
                        )}
                    </div>
                    <div className="font-sans text-[10px] tracking-[0.1em] text-stone">
                        {t(event.attendeeCount)}
                    </div>
                </div>

                <button
                    type="button"
                    disabled={event.joined}
                    onClick={onJoin}
                    className={cn(
                        'w-full cursor-pointer border px-3 py-3 font-sans text-[10px] font-medium tracking-[0.2em] uppercase transition-colors disabled:cursor-default',
                        event.joined
                            ? 'border-gold/25 bg-gold/25 text-gold'
                            : 'border-gold/25 bg-gold/10 text-gold hover:bg-gold/20',
                    )}
                >
                    {event.joined
                        ? t('✓ Aangemeld voor dit event')
                        : t(event.cta)}
                </button>
            </div>
        </article>
    );
}
