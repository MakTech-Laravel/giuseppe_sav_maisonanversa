import {
    useCallback,
    useEffect,
    useLayoutEffect,
    useRef,
    useState,
} from 'react';
import { useLocale } from '@/hooks/use-locale';
import { useScrollLock } from '@/hooks/use-scroll-lock';
import { OPENING_COPY, OPENING_SEEN_KEY } from './opening-data';
import { OpeningScene } from './opening-scene';

/** The fade the opening leaves with, over the home page already behind it. */
const LEAVE_MS = 600;

function readSession(key: string): string | null {
    try {
        return window.sessionStorage.getItem(key);
    } catch {
        // Storage can be denied outright, in which case the opening forgets.
        return null;
    }
}

function writeSession(key: string, value: string): void {
    try {
        window.sessionStorage.setItem(key, value);
    } catch {
        // As above.
    }
}

/**
 * The opening plays once per browser session; `?intro=1` plays it again. The
 * inline script in app.blade.php applies the same rule before first paint, by
 * setting <html data-opening="seen"> so that repeat visits never flash it.
 */
function shouldRun(): boolean {
    if (new URLSearchParams(window.location.search).has('intro')) {
        return true;
    }

    return readSession(OPENING_SEEN_KEY) === null;
}

/**
 * The arrival: one scene. The house draws itself, the door opens, and the eight
 * windows light up one by one; the room list appears beside it. Each window and
 * each name is a room.
 *
 * Mounted by the shell on the home page only. It is part of the server HTML, so
 * the house paints before any JavaScript has run; the first client render matches
 * that markup and the session check happens right after, before the next paint.
 * It never dismisses itself: the visitor leaves with Skip, Escape, or by
 * entering a room.
 */
export function Opening() {
    const { locale } = useLocale();
    const copy = OPENING_COPY[locale];

    const [open, setOpen] = useState(true);
    const [leaving, setLeaving] = useState(false);
    const [done, setDone] = useState(false);
    const [replays, setReplays] = useState(0);

    const leaveTimer = useRef<number | null>(null);

    useLayoutEffect(() => {
        // Deferred to after mount: sessionStorage is invisible to the server, and
        // reading it during render would desync from the server-rendered markup.
        if (!shouldRun()) {
            // eslint-disable-next-line react-hooks/set-state-in-effect
            setOpen(false);
        }
    }, []);

    useScrollLock(open);

    useEffect(
        () => () => {
            if (leaveTimer.current !== null) {
                window.clearTimeout(leaveTimer.current);
            }
        },
        [],
    );

    /** Leaves for good: remembered for the session, faded out over the home page. */
    const dismiss = useCallback(() => {
        writeSession(OPENING_SEEN_KEY, '1');

        if (leaveTimer.current !== null) {
            return;
        }

        const reduced = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        setLeaving(true);
        leaveTimer.current = window.setTimeout(
            () => setOpen(false),
            reduced ? 0 : LEAVE_MS,
        );
    }, []);

    const finish = useCallback(() => setDone(true), []);

    function replay() {
        setDone(false);
        setReplays((count) => count + 1);
    }

    useEffect(() => {
        if (!open) {
            return;
        }

        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                dismiss();

                return;
            }

            // Any other key ends the animation, as in the reference.
            if (event.key !== 'Tab') {
                setDone(true);
            }
        }

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [open, dismiss]);

    if (!open) {
        return null;
    }

    return (
        <div
            className={`ma-opening${leaving ? 'is-leaving' : ''}`}
            role="dialog"
            aria-modal="true"
            aria-label={copy.dialog}
            onScroll={finish}
        >
            <OpeningTopBar onSkip={dismiss} />

            <div className="op-scroll">
                <div className="op-sun" aria-hidden="true" />

                <div className="op-stage">
                    <OpeningScene
                        key={replays}
                        copy={copy}
                        locale={locale}
                        done={done}
                        onFinish={finish}
                        onReplay={replay}
                        onLeave={dismiss}
                    />
                </div>
            </div>
        </div>
    );
}

/** Language switcher (left) and Skip (right). */
function OpeningTopBar({ onSkip }: { onSkip: () => void }) {
    const { locale, availableLocales, switchLocale, t } = useLocale();

    return (
        <div className="op-top">
            <div className="op-lang" role="group" aria-label={t('Taal')}>
                {availableLocales.map((option) => (
                    <button
                        key={option}
                        type="button"
                        lang={option}
                        aria-current={option === locale ? 'true' : undefined}
                        onClick={() => void switchLocale(option)}
                    >
                        {option}
                    </button>
                ))}
            </div>

            <button type="button" className="op-skip" onClick={onSkip}>
                {t('Overslaan →')}
            </button>
        </div>
    );
}
