import {
    useCallback,
    useEffect,
    useLayoutEffect,
    useRef,
    useState,
} from 'react';
import type { MouseEvent as ReactMouseEvent } from 'react';

const SESSION_KEY = 'ma-door-opened';

function prefersReducedMotion(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function sessionAlreadyOpened(): boolean {
    try {
        return sessionStorage.getItem(SESSION_KEY) === '1';
    } catch {
        return false;
    }
}

function markSessionOpened(): void {
    try {
        sessionStorage.setItem(SESSION_KEY, '1');
    } catch {
        // Private mode / blocked storage — animation still plays this visit.
    }
}

/**
 * Session-once door opening: skip on click/key/scroll, replay, reduced motion.
 */
export function useHomeOpening() {
    const rootRef = useRef<HTMLElement | null>(null);
    const [isDone, setIsDone] = useState(false);
    const [ready, setReady] = useState(false);

    const finish = useCallback(() => {
        setIsDone(true);
    }, []);

    useLayoutEffect(() => {
        const skipAnimation =
            prefersReducedMotion() || sessionAlreadyOpened();

        if (skipAnimation) {
            setIsDone(true);
        }

        markSessionOpened();
        setReady(true);
    }, []);

    useEffect(() => {
        if (!ready || isDone) {
            return;
        }

        const onKey = () => finish();
        const onScroll = () => finish();

        window.addEventListener('keydown', onKey, { once: true });
        window.addEventListener('scroll', onScroll, {
            once: true,
            passive: true,
        });

        return () => {
            window.removeEventListener('keydown', onKey);
            window.removeEventListener('scroll', onScroll);
        };
    }, [ready, isDone, finish]);

    const onHeroClick = useCallback(
        (event: ReactMouseEvent<HTMLElement>) => {
            const target = event.target as HTMLElement | null;

            if (target?.closest('#opening-replay')) {
                return;
            }

            finish();
        },
        [finish],
    );

    const replay = useCallback(() => {
        const root = rootRef.current;

        if (!root) {
            return;
        }

        setIsDone(false);

        const animated = root.querySelectorAll<HTMLElement>('*');

        animated.forEach((node) => {
            node.style.animation = 'none';
        });

        void root.offsetWidth;

        animated.forEach((node) => {
            node.style.animation = '';
        });
    }, []);

    const className = [
        'home-opening',
        ready && isDone ? 'is-done' : '',
        ready ? 'is-ready' : 'is-pending',
    ]
        .filter(Boolean)
        .join(' ');

    return {
        rootRef,
        isDone,
        ready,
        className,
        onHeroClick,
        replay,
    };
}
