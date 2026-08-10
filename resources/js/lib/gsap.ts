import { useGSAP } from '@gsap/react';
import { gsap } from 'gsap';
import { CustomEase } from 'gsap/CustomEase';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

/*
 * Registered once, at module scope, so every hook shares one ticker and one
 * ScrollTrigger instance. `useGSAP` is registered too, which is what lets it
 * revert the animations a component created when that component unmounts.
 */
gsap.registerPlugin(useGSAP, ScrollTrigger, CustomEase);

/**
 * The prototype's `cubic-bezier(0.2, 0.8, 0.2, 1)`, reproduced exactly rather
 * than approximated with `power3.out`. Used by the reveals and the magnetic
 * buttons, which is where the site's motion signature comes from.
 */
export const MAISON_EASE = CustomEase.create('maison', '0.2,0.8,0.2,1');

/** The prototype's page-transition curve, `cubic-bezier(0.4, 0, 0.2, 1)`. */
export const TRANSITION_EASE = CustomEase.create(
    'maisonTransition',
    '0.4,0,0.2,1',
);

/**
 * The contexts every effect is declared inside. `gsap.matchMedia()` reverts
 * whatever a context created the moment it stops matching, so an effect cannot
 * outlive the conditions it was written for — a visitor switching on reduced
 * motion, or picking up a touch screen, gets the right behaviour without a
 * reload and without the effect having to remember to clean up.
 *
 * Nothing is registered under reduced motion; that case is the absence of all
 * three of these.
 */
export const MEDIA = {
    /** Motion is welcome, regardless of input device. */
    motion: '(prefers-reduced-motion: no-preference)',
    /** Motion plus a precise pointer: cursor, magnetic buttons, mouse parallax. */
    pointer: '(prefers-reduced-motion: no-preference) and (pointer: fine)',
    /** Motion on a touch screen: the gentler touch parallax. */
    touch: '(prefers-reduced-motion: no-preference) and (pointer: coarse)',
    /** Motion above the prototype's 560px mobile breakpoint. */
    motionDesktop:
        '(prefers-reduced-motion: no-preference) and (min-width: 561px)',
    /** Motion at or below it, where the reveals are shorter and travel less. */
    motionMobile:
        '(prefers-reduced-motion: no-preference) and (max-width: 560px)',
} as const;

export { gsap, ScrollTrigger, useGSAP };
