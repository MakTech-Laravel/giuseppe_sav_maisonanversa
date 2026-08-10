import type { ComponentProps, ElementType } from 'react';
import type { RevealVariant } from '@/hooks/use-reveal';

type RevealProps<T extends ElementType> = {
    as?: T;
    variant?: RevealVariant;
} & Omit<ComponentProps<T>, 'as'>;

/**
 * Marks a subtree to be revealed on scroll by `useReveal`, which the frontend
 * layout runs once for the whole page.
 *
 * The starting position is set by GSAP rather than by a class, so nothing is
 * hidden if the script never runs — an element left in its final state is a far
 * better failure than one stuck at `opacity: 0`, which is what the prototype's
 * `.reveal` class did.
 */
export function Reveal<T extends ElementType = 'div'>({
    as,
    variant = 'up',
    ...props
}: RevealProps<T>) {
    /*
     * Cast because TypeScript cannot prove a generic element type accepts the
     * props destined for it; the `RevealProps` signature is what holds callers
     * to the right ones.
     */
    const Component = (as ?? 'div') as ElementType;

    return <Component data-reveal={variant} {...props} />;
}
