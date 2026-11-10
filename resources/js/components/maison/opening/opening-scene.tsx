import { useEffect, useRef, useState } from 'react';
import type { Locale } from '@/types/locale';
import doorHouseArt from './art/door-house.html?raw';
import wordmarkArt from './art/wordmark.html?raw';
import { fillCopy, OPENING_MOODS, OPENING_ROOMS } from './opening-data';
import type { OpeningCopy } from './opening-data';
import { OpeningRegister } from './opening-register';
import { RoomList, RoomWindow } from './opening-rooms';
import { useOpeningRegister } from './use-opening-register';

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
 * The opening, as one scene in one column.
 *
 * The house draws itself, the door opens, and the eight windows light up one by
 * one, I → VIII. Once the door is open the wordmark and the edition appear under
 * the house, then the register with its two buttons, then the list of rooms.
 * Every window is a real transparent link over the artwork, and so is every
 * name in the list: either goes straight to the room's page. Hovering or focusing
 * one lights its window and shows a small label above it.
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
    /** The room whose window is lit: hovered or focused in the house or in the list. */
    const [hovered, setHovered] = useState<number | null>(null);
    const art = useRef<HTMLDivElement>(null);

    const register = useOpeningRegister();

    /*
     * A window that is hovered or focused, in the house or through its name in the
     * list, lights up fully (.is-on): that is what turns the soft daytime
     * reflection into a lit window.
     */
    useEffect(() => {
        OPENING_ROOMS.forEach((_, index) => {
            art.current
                ?.querySelector(`.lw${index}`)
                ?.classList.toggle('is-on', index === hovered);
        });
    }, [hovered]);

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
                <p className="op-greeting op-in op-t0">
                    {OPENING_MOODS.map((mood) => (
                        <span key={mood} className={`op-g op-g-${mood}`}>
                            {copy.greeting[mood]}
                        </span>
                    ))}
                </p>

                <div className="op-house" ref={art}>
                    <svg
                        className="mh-house"
                        viewBox="958 142 662 796"
                        aria-hidden="true"
                        focusable="false"
                        dangerouslySetInnerHTML={{ __html: doorHouseArt }}
                    />

                    {OPENING_ROOMS.map((entry, index) => (
                        <RoomWindow
                            key={entry.numeral}
                            room={entry}
                            index={index}
                            locale={locale}
                            hovered={hovered}
                            onHover={setHovered}
                            onLeave={onLeave}
                        />
                    ))}
                </div>

                {/* Under the house, and only once the door has opened. */}
                <header className="op-identity">
                    <h1>
                        <svg
                            className="ma-wordmark op-in op-t1"
                            role="img"
                            aria-label="Maison Anversa"
                            viewBox="672.727 496.492 1001.83 87.0156"
                            dangerouslySetInnerHTML={{ __html: wordmarkArt }}
                        />
                    </h1>

                    {/* The edition is the opening product's own name, so it follows the admin's choice. */}
                    {register !== null ? (
                        <>
                            <p className="op-edition op-in op-t2">
                                {register.name}
                            </p>
                            <p className="op-limit op-in op-t2">
                                {fillCopy(copy.limit, {
                                    total: register.total,
                                    n: register.available,
                                })}
                            </p>
                        </>
                    ) : null}
                </header>

                {register !== null ? (
                    <OpeningRegister
                        copy={copy}
                        register={register}
                        onLeave={onLeave}
                    />
                ) : null}

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

                <RoomList
                    copy={copy}
                    locale={locale}
                    hovered={hovered}
                    onHover={setHovered}
                    onLeave={onLeave}
                />
            </section>
        </div>
    );
}
