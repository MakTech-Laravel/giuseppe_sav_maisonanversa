import { useState } from 'react';
import type { Ref } from 'react';
import { MaisonLink } from '@/components/maison/maison-link';
import { foundingProductUrl } from '@/lib/maison-navigation';
import type { Locale } from '@/types/locale';
import { fillCopy, OPENING_ROOMS, openingPhotoSources } from './opening-data';
import type { OpeningCopy, OpeningRoom } from './opening-data';

type RoomListProps = {
    copy: OpeningCopy;
    locale: Locale;
    onPick: (index: number) => void;
};

/** The eight rooms as real buttons, beside the house. It appears once the windows are lit. */
export function RoomList({ copy, locale, onPick }: RoomListProps) {
    return (
        <div className="op-rooms op-in">
            <p className="op-k">{copy.roomsKicker}</p>
            <p className="op-hint">{copy.houseHint}</p>

            {OPENING_ROOMS.map((entry, index) => {
                const text = entry.text[locale];

                return (
                    <button
                        key={entry.numeral}
                        type="button"
                        className="hw-row"
                        onClick={() => onPick(index)}
                    >
                        <span className="hw-row-num">{entry.numeral}</span>
                        <span className="hw-row-name">{text.name}</span>
                        <span className="hw-row-sub">{text.subtitle}</span>
                    </button>
                );
            })}
        </div>
    );
}

type RoomCardProps = {
    room: OpeningRoom;
    copy: OpeningCopy;
    locale: Locale;
    onBack: () => void;
    /** The visitor is leaving the opening for the room's page. */
    onLeave: () => void;
    ref: Ref<HTMLDivElement>;
};

/**
 * A room's card: its photo, its line and the way in. The photo is requested only
 * when the card is on screen, never before a room is chosen.
 */
export function RoomCard({
    room,
    copy,
    locale,
    onBack,
    onLeave,
    ref,
}: RoomCardProps) {
    const [loaded, setLoaded] = useState(false);
    const text = room.text[locale];
    const photo = room.photo === null ? null : openingPhotoSources(room.photo);

    return (
        <div className="op-room" ref={ref} tabIndex={-1}>
            <p className="op-k">
                {fillCopy(copy.roomKicker, {
                    n: room.numeral,
                    sub: text.subtitle,
                })}
            </p>
            <h2>{text.name}</h2>
            <p className="op-line">{text.line}</p>

            <div className="op-photo">
                {photo !== null && (
                    <picture>
                        <source srcSet={photo.avif} type="image/avif" />
                        <img
                            src={photo.webp}
                            alt={text.name}
                            width={768}
                            height={512}
                            className={loaded ? 'ok' : undefined}
                            onLoad={() => setLoaded(true)}
                        />
                    </picture>
                )}
            </div>

            <div className="op-room-actions">
                {'page' in room.destination ? (
                    <MaisonLink
                        className="op-btn op-b1"
                        to={room.destination.page}
                        onClick={onLeave}
                    >
                        {text.enter}
                    </MaisonLink>
                ) : (
                    <MaisonLink
                        className="op-btn op-b1"
                        href={foundingProductUrl(locale)}
                        onClick={onLeave}
                    >
                        {text.enter}
                    </MaisonLink>
                )}

                <button type="button" className="op-btn op-b2" onClick={onBack}>
                    {copy.allRooms}
                </button>
            </div>
        </div>
    );
}
