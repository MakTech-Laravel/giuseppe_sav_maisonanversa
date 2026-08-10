import { useEffect } from 'react';

/*
 * One counter for the whole app. The prototype had two independent owners — the
 * intro set `body.intro-active` while modals set `body.style.overflow` — so
 * closing a modal that opened over the intro released the intro's lock and the
 * page scrolled behind it. Counting means the last holder releases the lock and
 * nobody else can.
 */
let holders = 0;
let restore: (() => void) | null = null;

function apply(): void {
    const { body } = document;
    const previousOverflow = body.style.overflow;
    const previousGutter = body.style.scrollbarGutter;

    body.style.overflow = 'hidden';
    // Reserves the scrollbar's width so locking does not shift the layout.
    body.style.scrollbarGutter = 'stable';

    restore = () => {
        body.style.overflow = previousOverflow;
        body.style.scrollbarGutter = previousGutter;
    };
}

export function lockScroll(): void {
    if (++holders === 1) {
        apply();
    }
}

export function unlockScroll(): void {
    if (holders === 0) {
        return;
    }

    if (--holders === 0) {
        restore?.();
        restore = null;
    }
}

/** Holds the page still while `locked` is true, releasing it on unmount. */
export function useScrollLock(locked: boolean): void {
    useEffect(() => {
        if (!locked) {
            return;
        }

        lockScroll();

        return unlockScroll;
    }, [locked]);
}

/** Test seam: the number of holders currently keeping the page locked. */
export function scrollLockHolders(): number {
    return holders;
}
