import { useEffect, useRef  } from 'react';
import type {ReactNode} from 'react';
import { useTranslation } from 'react-i18next';
import { useScrollLock } from '@/hooks/use-scroll-lock';
import { gsap, MAISON_EASE, MEDIA, TRANSITION_EASE, useGSAP } from '@/lib/gsap';
import { cn } from '@/lib/utils';

type MaisonModalProps = {
    /** Dutch source string passed to `t()` for the accessible name. */
    label: string;
    onClose: () => void;
    children: ReactNode;
    className?: string;
    panelClassName?: string;
};

const FOCUSABLE =
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

function useFocusTrap(
    container: React.RefObject<HTMLElement | null>,
    active: boolean,
): void {
    useEffect(() => {
        if (!active) {
            return;
        }

        const node = container.current;

        if (!node) {
            return;
        }

        const previous = document.activeElement as HTMLElement | null;

        function focusables(): HTMLElement[] {
            return Array.from(node!.querySelectorAll<HTMLElement>(FOCUSABLE)).filter(
                (element) =>
                    !element.hasAttribute('disabled') &&
                    element.tabIndex !== -1,
            );
        }

        focusables()[0]?.focus();

        function onKeyDown(event: KeyboardEvent): void {
            if (event.key !== 'Tab') {
                return;
            }

            const elements = focusables();

            if (elements.length === 0) {
                event.preventDefault();

                return;
            }

            const first = elements[0];
            const last = elements[elements.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        node.addEventListener('keydown', onKeyDown);

        return () => {
            node.removeEventListener('keydown', onKeyDown);
            previous?.focus();
        };
    }, [active, container]);
}

/**
 * The shared modal shell: overlay, panel, scroll lock, focus trap and Escape.
 *
 * The prototype toggled `body.style.overflow` per modal and lost the intro lock
 * when one closed over another. The counted utility in `use-scroll-lock` keeps
 * a single owner until the last modal releases it.
 */
export function MaisonModal({
    label,
    onClose,
    children,
    className,
    panelClassName,
}: MaisonModalProps) {
    const { t } = useTranslation();
    const root = useRef<HTMLDivElement>(null);
    const overlay = useRef<HTMLDivElement>(null);
    const panel = useRef<HTMLDivElement>(null);

    useScrollLock(true);
    useFocusTrap(panel, true);

    useEffect(() => {
        function onKeyDown(event: KeyboardEvent): void {
            if (event.key === 'Escape') {
                onClose();
            }
        }

        document.addEventListener('keydown', onKeyDown);

        return () => document.removeEventListener('keydown', onKeyDown);
    }, [onClose]);

    useGSAP(
        () => {
            const media = gsap.matchMedia();

            media.add(MEDIA.motion, () => {
                if (overlay.current) {
                    gsap.fromTo(
                        overlay.current,
                        { opacity: 0 },
                        {
                            opacity: 1,
                            duration: 0.35,
                            ease: TRANSITION_EASE,
                        },
                    );
                }

                if (panel.current) {
                    gsap.fromTo(
                        panel.current,
                        { opacity: 0, y: 20 },
                        {
                            opacity: 1,
                            y: 0,
                            duration: 0.45,
                            ease: MAISON_EASE,
                        },
                    );
                }
            });

            return () => media.revert();
        },
        { scope: root },
    );

    return (
        <div ref={root} className={cn('fixed inset-0 z-9990', className)}>
            <div
                ref={overlay}
                aria-hidden="true"
                className="absolute inset-0 bg-choc/92"
                onClick={onClose}
            />

            <div className="relative flex h-full items-center justify-center overflow-y-auto p-6 ma-sm:p-10">
                <div
                    ref={panel}
                    role="dialog"
                    aria-modal="true"
                    aria-label={t(label)}
                    className={cn(
                        'relative w-full max-w-[520px] bg-cream p-10 ma-sm:p-12',
                        panelClassName,
                    )}
                    onClick={(event) => event.stopPropagation()}
                >
                    <button
                        type="button"
                        onClick={onClose}
                        className="absolute top-5 right-5 font-sans text-lg text-stone transition-colors hover:text-choc"
                    >
                        <span aria-hidden="true">✕</span>
                        <span className="sr-only">{t('Sluiten')}</span>
                    </button>

                    {children}
                </div>
            </div>
        </div>
    );
}

export const modalInputClassName =
    'w-full border border-gold/25 bg-cream2 px-4 py-3 font-serif text-base text-choc outline-none focus:border-gold2';

export const modalNoteClassName =
    'mt-2 text-center font-sans text-[9px] tracking-[0.1em] text-stone';
