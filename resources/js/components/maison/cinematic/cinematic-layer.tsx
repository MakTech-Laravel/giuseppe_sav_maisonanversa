import { CustomCursor } from '@/components/maison/cinematic/custom-cursor';
import { useMagnetic } from '@/hooks/use-magnetic';

/**
 * The always-on half of the cinematic layer: grain, vignette, the custom cursor
 * and the magnetic-button listener.
 *
 * Rendered once by the site shell. Grain and vignette are fixed overlays that
 * sit above the page and below the cursor, so their stacking order is fixed in
 * CSS rather than left to the order they appear here.
 */
export function CinematicLayer() {
    useMagnetic();

    return (
        <>
            <div className="cine-grain" aria-hidden="true" />
            <div className="cine-vignette" aria-hidden="true" />
            <CustomCursor />
        </>
    );
}
