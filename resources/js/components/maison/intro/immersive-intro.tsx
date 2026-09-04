import { usePage } from '@inertiajs/react';
import {
    useCallback,
    useEffect,
    useLayoutEffect,
    useMemo,
    useRef,
    useState,
} from 'react';
import { useTranslation } from 'react-i18next';
import { Preloader } from '@/components/maison/cinematic/preloader';
import { RoomPanels } from '@/components/maison/intro/room-panels';
import { MaisonLink } from '@/components/maison/maison-link';
import { LanguageSwitcher } from '@/components/maison/shell/language-switcher';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { useLocale } from '@/hooks/use-locale';
import { useScrollLock } from '@/hooks/use-scroll-lock';
import { gsap, MAISON_EASE, useGSAP } from '@/lib/gsap';
import { imageAsset } from '@/lib/imagery';
import {
    CLOSING_SLIDE,
    INTRO_ROOMS,
    INTRO_SLIDES,
    introCopy,
    introPanel,
    removeBootCover,
    ROOM_COUNT,
} from '@/lib/maison-intro';
import { foundingProductUrl } from '@/lib/maison-navigation';
import { cn } from '@/lib/utils';

/** The prototype's `setTimeout(..., 15000)`, restarted on every advance. */
const IDLE_ENTER_MS = 15000;

/** Its `isAnimating` guard: one slide per crossfade. */
const SLIDE_LOCK_MS = 800;

/** The loader stays up at least long enough to finish its own choreography. */
const LOADER_FLOOR_MS = 2400;

/** And never longer than this, however the photographs are getting on. */
const LOADER_CEILING_MS = 5000;

/** The swipe the prototype accepted, in pixels along the dominant axis. */
const SWIPE = 50;

const SEEN_KEY = 'maison.intro.seen';
const SLIDE_KEY = 'maison.intro.slide';

function readSession(key: string): string | null {
    try {
        return window.sessionStorage.getItem(key);
    } catch {
        // Storage can be denied outright, in which case the intro forgets.
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

/** `?p=0..n` opens on a given slide, for screenshots and QA. */
function requestedSlide(params: URLSearchParams): number | null {
    const requested = Number(params.get('p'));

    return params.has('p') &&
        Number.isInteger(requested) &&
        requested >= 0 &&
        requested < INTRO_SLIDES.length
        ? requested
        : null;
}

/**
 * The intro is an arrival, so it plays once per session: returning to the home
 * page from a room does not replay it. `?intro=1` asks for it again.
 *
 * Reduced motion skips it altogether. It is a sequence of drifting photography
 * with no still equivalent, and the home page behind it says the same things —
 * the prototype came to the same conclusion.
 */
function shouldRun(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    const params = new URLSearchParams(window.location.search);

    if (params.has('intro') || requestedSlide(params) !== null) {
        return true;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return false;
    }

    return readSession(SEEN_KEY) === null;
}

/**
 * Which slide to open on: the QA parameter first, then wherever the visitor had
 * reached before switching language, which reloads under a different prefix.
 */
function openingSlide(): number {
    const params = new URLSearchParams(window.location.search);
    const requested = requestedSlide(params);

    if (requested !== null) {
        return requested;
    }

    const resumed = Number(readSession(SLIDE_KEY));

    return Number.isInteger(resumed) &&
        resumed > 0 &&
        resumed < INTRO_SLIDES.length
        ? resumed
        : 0;
}

/**
 * The eight-room arrival sequence, over the home page.
 *
 * Mounted by the shell on the home page only. The stage itself lives in a child
 * component so that none of its listeners, timers or scroll lock exist once the
 * visitor is inside the house.
 */
export function ImmersiveIntro() {
    // Always start false so SSR HTML matches the first client render. The
    // session/motion check runs after mount; otherwise hydration remounts the
    // tree and can leave #maison-boot-cover stuck on screen.
    const [running, setRunning] = useState(false);

    useLayoutEffect(() => {
        const next = shouldRun();

        // Deliberately deferred to after mount: `shouldRun()` reads
        // sessionStorage and prefers-reduced-motion, which SSR cannot see.
        // Computing this during render would desync from the server-rendered
        // markup and force a hydration remount (see comment above).
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setRunning(next);

        if (!next) {
            removeBootCover();
        }
    }, []);

    if (!running) {
        return null;
    }

    return <IntroStage onDismissed={() => setRunning(false)} />;
}

function IntroStage({ onDismissed }: { onDismissed: () => void }) {
    const availableImages = usePage().props.availableImages;
    const { locale } = useLocale();
    const { t } = useTranslation();

    const [slide, setSlide] = useState(openingSlide);
    const [curtainLifted, setCurtainLifted] = useState(false);

    const stage = useRef<HTMLDivElement>(null);
    const label = useRef<HTMLDivElement>(null);
    const doorway = useRef<HTMLDivElement>(null);
    const closingCard = useRef<HTMLDivElement>(null);

    /*
     * The slide the listeners should act on. Bound once from a ref rather than
     * rebound per slide, so a swipe that lands mid-crossfade is still measured
     * against the room actually on screen.
     */
    const current = useRef(slide);
    const lockedUntil = useRef(0);
    const dismissed = useRef(false);

    const copy = introCopy(slide, locale);
    const isClosing = slide === CLOSING_SLIDE;
    const { destination } = INTRO_SLIDES[slide];

    useScrollLock(true);

    useEffect(() => {
        current.current = slide;
    }, [slide]);

    /* ── The loader ─────────────────────────────────────────────────────── */

    /*
     * Only photographs that exist are waited for. Until the real imagery lands
     * the panels are brand-palette placeholders, which load nothing, so the bar
     * would otherwise sit at zero for its full five seconds.
     */
    const pending = useMemo(() => {
        const images = availableImages ?? [];

        return INTRO_ROOMS.map((room) => imageAsset(room).path).filter((path) =>
            images.includes(path),
        );
    }, [availableImages]);

    const [loaded, setLoaded] = useState(0);
    const [floorPassed, setFloorPassed] = useState(false);
    const [ceilingPassed, setCeilingPassed] = useState(false);

    const progress = pending.length === 0 ? 1 : loaded / pending.length;
    const loaderDone = ceilingPassed || (progress >= 1 && floorPassed);

    useEffect(() => {
        let live = true;
        const count = () => {
            if (live) {
                setLoaded((done) => done + 1);
            }
        };

        for (const path of pending) {
            const image = new Image();

            // A missing or broken file must not hold the curtain down.
            image.onload = count;
            image.onerror = count;
            image.src = `/${path}`;
        }

        return () => {
            live = false;
        };
    }, [pending]);

    useEffect(() => {
        const floor = window.setTimeout(
            () => setFloorPassed(true),
            LOADER_FLOOR_MS,
        );
        const ceiling = window.setTimeout(
            () => setCeilingPassed(true),
            LOADER_CEILING_MS,
        );

        return () => {
            window.clearTimeout(floor);
            window.clearTimeout(ceiling);
        };
    }, []);

    /* ── Leaving ────────────────────────────────────────────────────────── */

    const markSeen = useCallback(() => {
        writeSession(SEEN_KEY, '1');
        writeSession(SLIDE_KEY, '0');
    }, []);

    /** Fades the stage away over the home page already rendered behind it. */
    const dismiss = useCallback(() => {
        if (dismissed.current) {
            return;
        }

        dismissed.current = true;
        markSeen();
        removeBootCover();

        gsap.to(stage.current, {
            opacity: 0,
            duration: 0.9,
            ease: 'power2.inOut',
            onComplete: onDismissed,
        });
    }, [markSeen, onDismissed]);

    /* ── Walking through ────────────────────────────────────────────────── */

    const goTo = useCallback((next: number) => {
        const now = Date.now();

        if (
            next < 0 ||
            next >= INTRO_SLIDES.length ||
            next === current.current ||
            now < lockedUntil.current
        ) {
            return;
        }

        lockedUntil.current = now + SLIDE_LOCK_MS;
        current.current = next;
        setSlide(next);
        writeSession(SLIDE_KEY, String(next));
    }, []);

    useEffect(() => {
        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                event.preventDefault();
                goTo(current.current + 1);
            } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                event.preventDefault();
                goTo(current.current - 1);
            } else if (event.key === 'Escape') {
                dismiss();
            }
        }

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [dismiss, goTo]);

    useEffect(() => {
        let startX = 0;
        let startY = 0;
        let tracking = false;

        function onStart(event: TouchEvent) {
            const touch = event.touches[0];

            if (touch) {
                startX = touch.clientX;
                startY = touch.clientY;
                tracking = true;
            }
        }

        function onEnd(event: TouchEvent) {
            const touch = event.changedTouches[0];

            if (!tracking || !touch) {
                return;
            }

            tracking = false;

            const x = touch.clientX - startX;
            const y = touch.clientY - startY;
            const horizontal = Math.abs(x) > Math.abs(y);
            const travel = horizontal ? x : y;

            // Left and up both go deeper into the house; right and down back.
            if (Math.abs(travel) > SWIPE) {
                goTo(current.current + (travel < 0 ? 1 : -1));
            }
        }

        const passive = { passive: true } as const;

        window.addEventListener('touchstart', onStart, passive);
        window.addEventListener('touchend', onEnd, passive);

        return () => {
            window.removeEventListener('touchstart', onStart);
            window.removeEventListener('touchend', onEnd);
        };
    }, [goTo]);

    /*
     * The prototype entered the house 15 seconds after load whatever the visitor
     * was doing, which cut off anyone still reading a room. Restarting the
     * countdown on each advance keeps the unattended-screen behaviour without
     * the ambush.
     */
    useEffect(() => {
        if (!curtainLifted) {
            return;
        }

        const timer = window.setTimeout(dismiss, IDLE_ENTER_MS);

        return () => window.clearTimeout(timer);
    }, [curtainLifted, dismiss, slide]);

    /* Announces the overlay, and gives the arrow keys somewhere to belong. */
    useEffect(() => {
        if (curtainLifted) {
            removeBootCover();
            stage.current?.focus();
        }
    }, [curtainLifted]);

    /* ── The choreography ───────────────────────────────────────────────── */

    useGSAP(
        () => {
            if (!curtainLifted) {
                return;
            }

            if (label.current) {
                gsap.fromTo(
                    label.current,
                    { opacity: 0, y: 12 },
                    { opacity: 1, y: 0, duration: 0.9, ease: MAISON_EASE },
                );
            }

            // Held back so the room is read before it offers to be entered.
            if (doorway.current) {
                gsap.fromTo(
                    doorway.current,
                    { opacity: 0 },
                    { opacity: 0.9, duration: 0.6, delay: 0.4 },
                );
            }

            if (closingCard.current) {
                gsap.fromTo(
                    closingCard.current,
                    { opacity: 0, y: 14 },
                    { opacity: 1, y: 0, duration: 1, ease: MAISON_EASE },
                );
            }
        },
        { dependencies: [slide, curtainLifted] },
    );

    return (
        <div
            ref={stage}
            role="dialog"
            aria-modal="true"
            aria-label="Maison Anversa"
            tabIndex={-1}
            className="fixed inset-0 z-9999 overflow-hidden bg-choc outline-none"
        >
            {/*
             * Held back until the curtain has fully lifted — not merely until
             * the loader *starts* exiting — so the doorway never blinks through
             * the fading chocolate wash.
             */}
            <RoomPanels panel={curtainLifted ? introPanel(slide) : -1} />

            {curtainLifted && (
                <>
                    {/* The prototype's `.overlay::after`: clear in the middle, dark at the edges. */}
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_15%,rgba(41,28,24,0.65)_55%,rgba(41,28,24,0.96)_100%)] ma-sm:bg-[radial-gradient(ellipse_at_center,transparent_30%,rgba(41,28,24,0.5)_70%,rgba(41,28,24,0.9)_100%)]"
                    />

                    <p className="absolute inset-x-0 top-5.5 text-center font-sans text-[12px] tracking-[0.42em] text-cream uppercase ma-sm:top-8.5 ma-sm:text-[13px] ma-sm:text-gold ma-sm:opacity-92">
                        Maison Anversa
                        <small className="mt-1.5 block font-sans text-[10px] tracking-[0.22em] text-cream/85 italic ma-sm:text-[9px] ma-sm:text-sand/70">
                            {t('Antwerpen · MMXXVI')}
                        </small>
                    </p>

                    {!isClosing && (
                        <div
                            ref={label}
                            className="absolute inset-x-0 top-1/2 -translate-y-1/2 px-16 text-center opacity-0 ma-sm:px-7"
                        >
                            <span className="block font-sans text-[11px] tracking-[0.34em] text-cream/85 uppercase ma-sm:text-[10px] ma-sm:text-gold ma-sm:opacity-90">
                                {copy.eyebrow}
                            </span>
                            <span className="mt-3.5 block font-serif text-[clamp(28px,8vw,48px)] leading-[1.06] tracking-[0.01em] text-cream [text-shadow:0_2px_40px_rgba(0,0,0,0.7)] ma-sm:text-[clamp(34px,6vw,64px)]">
                                {copy.opening}
                                <em className="text-gold italic">
                                    {copy.emphasis}
                                </em>
                            </span>
                            <span className="mt-3.5 block font-sans text-[12px] tracking-[0.16em] text-cream/90 uppercase ma-sm:text-[13px] ma-sm:text-sand/82">
                                {copy.subtitle}
                            </span>
                        </div>
                    )}

                    {destination && !isClosing && (
                        <div
                            /* Remounted per room so the delayed entrance replays. */
                            key={slide}
                            ref={doorway}
                            className="absolute bottom-[18%] left-1/2 -translate-x-1/2 opacity-0 ma-sm:bottom-[22%]"
                        >
                            <MaisonButton
                                as={MaisonLink}
                                variant="intro"
                                {...(destination === 'founding-product'
                                    ? { href: foundingProductUrl(locale) }
                                    : { to: destination })}
                                onClick={markSeen}
                            >
                                {t('Betreed kamer →')}
                            </MaisonButton>
                        </div>
                    )}

                    {isClosing && (
                        <div
                            ref={closingCard}
                            className="absolute inset-x-0 bottom-[10%] px-6 text-center opacity-0 ma-sm:bottom-[14%]"
                        >
                            <span className="block font-sans text-[11px] tracking-[0.32em] text-cream uppercase ma-sm:text-[10px] ma-sm:text-gold">
                                {t('U heeft het huis doorlopen')}
                            </span>
                            <span className="mt-4 mb-6.5 block font-serif text-[clamp(26px,7vw,42px)] text-cream ma-sm:text-[clamp(30px,5vw,52px)]">
                                {copy.opening}
                                <em className="text-gold italic">
                                    {copy.emphasis}
                                </em>
                            </span>
                            <MaisonButton
                                variant="intro"
                                onClick={dismiss}
                                className="px-8 py-3.5 text-[12px] ma-sm:px-9.5 ma-sm:py-4 ma-sm:text-[11px] ma-sm:tracking-[0.28em]"
                            >
                                {t('Betreed het Huis')}
                            </MaisonButton>
                        </div>
                    )}

                    <IntroArrow
                        direction="prev"
                        label={t('Vorige kamer')}
                        hidden={slide === 0}
                        onClick={() => goTo(slide - 1)}
                    />
                    <IntroArrow
                        direction="next"
                        label={t('Volgende kamer')}
                        hidden={isClosing}
                        onClick={() => goTo(slide + 1)}
                    />

                    {/*
                     * Faded rather than removed, so it leaves quietly — and hidden from
                     * assistive technology at the same time, because a transparent
                     * instruction is still read out loud.
                     */}
                    <p
                        aria-hidden={slide !== 0}
                        className={cn(
                            'pointer-events-none absolute inset-x-0 bottom-11 text-center font-sans text-[11px] tracking-[0.24em] text-cream uppercase transition-opacity duration-800 ma-sm:bottom-15 ma-sm:text-[10px] ma-sm:text-gold',
                            slide === 0 ? 'opacity-60' : 'opacity-0',
                        )}
                    >
                        {t('Swipe of tik › om te beginnen')}
                    </p>

                    {/* Counts the rooms; the closing card is not one of them. */}
                    <p
                        aria-hidden={isClosing}
                        className={cn(
                            'absolute bottom-5.5 left-1/2 -translate-x-1/2 font-sans text-[11px] tracking-[0.3em] text-cream transition-opacity duration-500 ma-sm:bottom-8.5 ma-sm:text-sand',
                            isClosing
                                ? 'opacity-0'
                                : 'opacity-90 ma-sm:opacity-70',
                        )}
                    >
                        {String(Math.min(slide + 1, ROOM_COUNT)).padStart(
                            2,
                            '0',
                        )}{' '}
                        / {String(ROOM_COUNT).padStart(2, '0')}
                    </p>

                    {/* Decorative: the counter beside it already states the position. */}
                    <div
                        aria-hidden="true"
                        className="absolute bottom-7.5 left-1/2 hidden -translate-x-1/2 items-center gap-3.5 ma-sm:flex"
                    >
                        {INTRO_SLIDES.map((entry, index) => (
                            <i
                                key={entry.copy.nl.eyebrow}
                                className={cn(
                                    'block h-px transition-all duration-500',
                                    index <= slide
                                        ? 'w-10.5 bg-gold'
                                        : 'w-6.5 bg-cream/22',
                                )}
                            />
                        ))}
                    </div>

                    <LanguageSwitcher className="absolute right-6 bottom-15 z-20" />

                    <button
                        type="button"
                        onClick={dismiss}
                        className="absolute top-1.5 right-1.5 z-20 p-4 font-sans text-[11px] tracking-[0.24em] text-cream/80 uppercase transition-colors hover:text-gold ma-sm:top-3.5 ma-sm:right-3.5 ma-sm:p-2 ma-sm:text-[9px] ma-sm:text-sand/50"
                    >
                        {t('Overslaan →')}
                    </button>
                </>
            )}

            {!curtainLifted && (
                <Preloader
                    progress={progress}
                    done={loaderDone}
                    onFinished={() => setCurtainLifted(true)}
                />
            )}
        </div>
    );
}

function IntroArrow({
    direction,
    label,
    hidden,
    onClick,
}: {
    direction: 'prev' | 'next';
    label: string;
    hidden: boolean;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={label}
            /*
             * At either end of the sequence the arrow fades out rather than
             * disappearing, so it also has to leave the tab order and the
             * accessibility tree — otherwise it announces a room that is not
             * there and swallows a keypress.
             */
            aria-hidden={hidden}
            tabIndex={hidden ? -1 : undefined}
            className={cn(
                'absolute top-1/2 z-10 flex size-11.5 -translate-y-1/2 items-center justify-center rounded-full border border-cream/40 bg-choc/60 font-serif text-2xl leading-none text-cream backdrop-blur-xs transition-all duration-400 hover:border-cream hover:bg-cream hover:text-choc ma-sm:size-13 ma-sm:border-gold/40 ma-sm:bg-choc/40 ma-sm:text-[28px] ma-sm:text-gold ma-sm:hover:border-gold ma-sm:hover:bg-gold ma-sm:hover:text-choc',
                direction === 'prev'
                    ? 'left-3 ma-sm:left-7'
                    : 'right-3 ma-sm:right-7',
                hidden && 'pointer-events-none opacity-0',
            )}
        >
            <span aria-hidden="true">{direction === 'prev' ? '‹' : '›'}</span>
        </button>
    );
}
