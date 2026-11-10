import { MaisonLink } from '@/components/maison/maison-link';
import { cn } from '@/lib/utils';
import type { Locale } from '@/types/locale';
import { OPENING_ROOMS, roomHref } from './opening-data';
import type { OpeningCopy, OpeningRoom } from './opening-data';

type RoomLinkProps = {
    room: OpeningRoom;
    index: number;
    locale: Locale;
    /** The room whose window is lit by a hover or focus, in the house or in the list. */
    hovered: number | null;
    onHover: (index: number | null) => void;
    /** The visitor is leaving the opening for the room's page. */
    onLeave: () => void;
};

/**
 * A window of the house: a real link over the artwork, straight to the room's
 * page. Hover or keyboard focus lights the window (the scene does that through
 * `hovered`) and shows a small label above it, e.g. "Bibliothèque · Journal".
 */
export function RoomWindow({
    room,
    index,
    locale,
    hovered,
    onHover,
    onLeave,
}: RoomLinkProps) {
    const text = room.text[locale];

    return (
        <MaisonLink
            href={roomHref(room, locale)}
            className={cn('hw-btn', hovered === index && 'hov')}
            aria-label={`${text.name}, ${text.subtitle}`}
            onClick={onLeave}
            onPointerEnter={() => onHover(index)}
            onPointerLeave={() => onHover(null)}
            onFocus={() => onHover(index)}
            onBlur={() => onHover(null)}
            style={{
                left: `${room.hotspot.left}%`,
                top: `${room.hotspot.top}%`,
                width: `${room.hotspot.width}%`,
                height: `${room.hotspot.height}%`,
            }}
        >
            <span className="op-tip" aria-hidden="true">
                {text.name} · {text.subtitle}
            </span>
        </MaisonLink>
    );
}

type RoomListProps = {
    copy: OpeningCopy;
    locale: Locale;
    hovered: number | null;
    onHover: (index: number | null) => void;
    onLeave: () => void;
};

/**
 * The eight rooms as links, in the register's column under it. The whole row is a
 * link to the room's page; hovering or focusing one lights the same window in the
 * house, and the other way round. It appears once the windows are lit.
 */
export function RoomList({
    copy,
    locale,
    hovered,
    onHover,
    onLeave,
}: RoomListProps) {
    return (
        <section className="op-rooms op-in" aria-labelledby="op-rooms-title">
            <p className="op-k" id="op-rooms-title">
                {copy.roomsKicker}
            </p>
            <p className="op-hint">{copy.houseHint}</p>

            <ol className="op-rl">
                {OPENING_ROOMS.map((room, index) => {
                    const text = room.text[locale];

                    return (
                        <li key={room.numeral}>
                            <MaisonLink
                                href={roomHref(room, locale)}
                                className={cn(hovered === index && 'hov')}
                                onClick={onLeave}
                                onPointerEnter={() => onHover(index)}
                                onPointerLeave={() => onHover(null)}
                                onFocus={() => onHover(index)}
                                onBlur={() => onHover(null)}
                            >
                                <span className="op-rl-n">{room.numeral}</span>
                                <span className="op-rl-nm">{text.name}</span>
                                <span className="op-rl-lb">
                                    {text.subtitle}
                                </span>
                                <span className="op-rl-ar" aria-hidden="true">
                                    →
                                </span>
                            </MaisonLink>
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}
