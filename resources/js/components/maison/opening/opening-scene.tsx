import { useEffect, useRef, useState } from 'react';
import type { Locale } from '@/types/locale';
import doorHouseArt from './art/door-house.html?raw';
import wordmarkArt from './art/wordmark.html?raw';
import {
    fillCopy,
    OPENING_EDITION_SIZE,
    OPENING_MOODS,
    OPENING_RESERVED_NUMBERS,
    OPENING_ROOMS,
} from './opening-data';
import type { OpeningCopy } from './opening-data';
import { OpeningRegister } from './opening-register';
import { RoomCard, RoomList } from './opening-rooms';

type OpeningSceneProps = {
    copy: OpeningCopy;
    locale: Locale;
    /** End state: no animation (tap, key, scroll, reduced motion). */
    done: boolean;
    onFinish: () => void;
    /** Replays the animation. Unused while "Open the door again" is commented out. */
    onReplay?: () => void;
    /** The visitor is leaving the opening for another page. */
    onLeave: () => void;
};

/**
 * The opening, as one scene.
 *
 * The house draws itself, the door opens, and the eight windows light up one by
 * one, I → VIII. Beside it sit the wordmark and the room list. Every window is a
 * real transparent button over the artwork, and so is every name: either opens
 * the room's card in the same place, with its photo and the way in.
 *
 * The artwork is the client's SVG, rendered as markup so it is in the server
 * HTML and paints before any JavaScript has run. The greeting carries all four
 * moods and CSS shows the one that matches <html data-light>, for the same reason.
 */
export function OpeningScene({
    copy,
    locale,
    done,
    onFinish,
    onLeave,
}: OpeningSceneProps) {
    const [selected, setSelected] = useState<number | null>(null);
    const art = useRef<HTMLDivElement>(null);
    const card = useRef<HTMLDivElement>(null);

    const available = OPENING_EDITION_SIZE - OPENING_RESERVED_NUMBERS.length;
    const room = selected === null ? null : OPENING_ROOMS[selected];

    function pick(index: number | null) {
        setSelected((current) => (current === index ? null : index));
    }

    /* The chosen window brightens: the artwork's own .hw-sel overlays. */
    useEffect(() => {
        art.current?.querySelectorAll<SVGElement>('.hw-sel').forEach((node) => {
            node.classList.toggle('on', Number(node.dataset.i) === selected);
        });

        if (selected !== null) {
            card.current?.focus({ preventScroll: true });
        }
    }, [selected]);

    return (
        <div className={done ? 'is-done' : undefined}>
            <section
                className="op-scene"
                onClick={(event) => {
                    if (!(event.target as HTMLElement).closest('.op-replay')) {
                        onFinish();
                    }
                }}
            >
                {/* Fixed at the top: it does not change when a room is chosen. */}
                <header className="op-identity">
                    <p className="op-greeting op-in op-t0">
                        {OPENING_MOODS.map((mood) => (
                            <span key={mood} className={`op-g op-g-${mood}`}>
                                {copy.greeting[mood]}
                            </span>
                        ))}
                    </p>

                    <h1>
                        <svg
                            className="ma-wordmark op-in op-t1"
                            role="img"
                            aria-label="Maison Anversa"
                            viewBox="672.727 496.492 1001.83 87.0156"
                            dangerouslySetInnerHTML={{ __html: wordmarkArt }}
                        />
                    </h1>

                    <p className="op-edition op-in op-t2">{copy.edition}</p>
                    <p className="op-limit op-in op-t2">
                        {fillCopy(copy.limit, { n: available })}
                    </p>
                </header>

                <div className="op-cols">
                    <div className="op-house" ref={art}>
                        <svg
                            className="mh-house"
                            viewBox="958 142 662 796"
                            aria-hidden="true"
                            focusable="false"
                            dangerouslySetInnerHTML={{ __html: doorHouseArt }}
                        />

                        {OPENING_ROOMS.map((entry, index) => {
                            const text = entry.text[locale];

                            return (
                                <button
                                    key={entry.numeral}
                                    type="button"
                                    className="hw-btn"
                                    aria-label={`${text.name}, ${text.subtitle}`}
                                    aria-pressed={selected === index}
                                    onClick={() => pick(index)}
                                    style={{
                                        left: `${entry.hotspot.left}%`,
                                        top: `${entry.hotspot.top}%`,
                                        width: `${entry.hotspot.width}%`,
                                        height: `${entry.hotspot.height}%`,
                                    }}
                                />
                            );
                        })}
                    </div>

                    <div className="op-side">
                        {room === null ? (
                            <>
                                <RoomList
                                    copy={copy}
                                    locale={locale}
                                    onPick={pick}
                                />

                                {/*
                                 * "Open the door again": switched off for now. To bring it
                                 * back, uncomment this and destructure `onReplay` above.
                                 *
                                 * <button
                                 *     type="button"
                                 *     className="op-replay op-in op-t3"
                                 *     onClick={onReplay}
                                 * >
                                 *     {copy.replay}
                                 * </button>
                                 */}
                            </>
                        ) : (
                            <RoomCard
                                key={selected}
                                ref={card}
                                room={room}
                                copy={copy}
                                locale={locale}
                                onBack={() => pick(null)}
                                onLeave={onLeave}
                            />
                        )}
                    </div>
                </div>
            </section>

            <OpeningRegister copy={copy} onLeave={onLeave} />
        </div>
    );
}
