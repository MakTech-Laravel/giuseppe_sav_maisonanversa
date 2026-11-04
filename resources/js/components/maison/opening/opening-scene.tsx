import { useEffect, useRef, useState } from 'react';
import type { Locale } from '@/types/locale';
import doorHouseArt from './art/door-house.html?raw';
import wordmarkArt from './art/wordmark.html?raw';
import { fillCopy, OPENING_MOODS, OPENING_ROOMS } from './opening-data';
import type { OpeningCopy } from './opening-data';
import { OpeningRegister } from './opening-register';
import { RoomCard, RoomList } from './opening-rooms';
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
 * Every window is a real transparent button over the artwork, and so is every
 * name: either opens the room's photo, full screen, with the way in on top of it.
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
    const [hovered, setHovered] = useState<number | null>(null);
    const art = useRef<HTMLDivElement>(null);
    const card = useRef<HTMLDivElement>(null);

    const register = useOpeningRegister();
    const room = selected === null ? null : OPENING_ROOMS[selected];

    function pick(index: number | null) {
        setSelected((current) => (current === index ? null : index));
        setHovered(null);
    }

    /*
     * The chosen window brightens: the artwork's own .hw-sel overlays. A window
     * that is hovered, focused or chosen also lights up fully (.is-on), which is
     * what turns the soft daytime reflection into a lit window.
     */
    useEffect(() => {
        art.current?.querySelectorAll<SVGElement>('.hw-sel').forEach((node) => {
            node.classList.toggle('on', Number(node.dataset.i) === selected);
        });

        OPENING_ROOMS.forEach((_, index) => {
            art.current
                ?.querySelector(`.lw${index}`)
                ?.classList.toggle(
                    'is-on',
                    index === selected || index === hovered,
                );
        });

        if (selected !== null) {
            card.current?.focus({ preventScroll: true });
        }
    }, [selected, hovered]);

    /*
     * Escape closes the photo first. It listens in the capture phase so that it
     * runs before the opening's own Escape, which would leave the whole opening.
     */
    useEffect(() => {
        if (selected === null) {
            return;
        }

        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                event.stopImmediatePropagation();
                setSelected(null);
            }
        }

        window.addEventListener('keydown', onKeyDown, true);

        return () => window.removeEventListener('keydown', onKeyDown, true);
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
                                onPointerEnter={() => setHovered(index)}
                                onPointerLeave={() => setHovered(null)}
                                onFocus={() => setHovered(index)}
                                onBlur={() => setHovered(null)}
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

                <RoomList copy={copy} locale={locale} onPick={pick} />
            </section>

            {room !== null ? (
                <RoomCard
                    key={selected}
                    ref={card}
                    room={room}
                    copy={copy}
                    locale={locale}
                    onBack={() => pick(null)}
                    onLeave={onLeave}
                />
            ) : null}
        </div>
    );
}
