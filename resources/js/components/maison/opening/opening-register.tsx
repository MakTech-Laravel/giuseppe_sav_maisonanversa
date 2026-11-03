import { useState } from 'react';
import type { CSSProperties } from 'react';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { usePurchasesPaused } from '@/hooks/use-purchases-paused';
import type { OpeningRegisterData } from '@/types/opening';
import type { OpeningCopy } from './opening-data';

type OpeningRegisterProps = {
    copy: OpeningCopy;
    register: OpeningRegisterData;
    /** The visitor is leaving the opening, for the home page or for the reservation. */
    onLeave: () => void;
};

/**
 * The register: the edition numbers of the product an admin chose for the opening,
 * under the house, and the two ways out of it.
 *
 * - Available numbers print as available; every other number (held, sold, returning,
 *   not for sale) prints in the "reserved" colour. There is no fixed count.
 * - "Choose your number" starts the site's own reservation for that product: the
 *   shell's `openPurchase()`, which asks a visitor who is not signed in to log in,
 *   then opens the edition picker (the live list of numbers) and the order modal.
 *   It leaves the opening first, because those modals sit below it.
 * - "Enter the house" leaves the opening for the home page behind it.
 *
 * A tile highlights but does not pre-select a number in the picker: the picker is
 * where the number is chosen and held, so the purchase procedure stays as it was.
 */
export function OpeningRegister({
    copy,
    register,
    onLeave,
}: OpeningRegisterProps) {
    const { openPurchase } = useShellActions();
    const purchasesPaused = usePurchasesPaused();
    const [selected, setSelected] = useState<number | null>(null);

    // Labels such as EXC001ST are wider than 001, so the tiles size to the longest.
    const longestLabel = register.tiles.reduce(
        (longest, tile) => Math.max(longest, tile.label.length),
        0,
    );

    function pick(id: number) {
        setSelected((current) => (current === id ? null : id));
    }

    function reserve() {
        onLeave();
        openPurchase(register.product);
    }

    return (
        <section
            id="op-register"
            className="op-register op-in op-t3"
            aria-labelledby="op-register-title"
        >
            <div className="op-reg-head">
                <h2 id="op-register-title">{copy.registerTitle}</h2>
                <div className="op-legend">
                    <span>
                        <i className="op-sw op-sw-a" />
                        {copy.available}
                    </span>
                    <span>
                        <i className="op-sw op-sw-r" />
                        {copy.reserved}
                    </span>
                </div>
            </div>

            <div
                className="op-numbers"
                style={
                    {
                        '--op-tile': `${Math.max(52, longestLabel * 9 + 20)}px`,
                    } as CSSProperties
                }
            >
                {register.tiles.map((tile) => (
                    <button
                        key={tile.id}
                        type="button"
                        className="op-num"
                        disabled={!tile.available}
                        aria-pressed={selected === tile.id}
                        aria-label={`Nº ${tile.label}, ${
                            tile.available
                                ? copy.tileAvailable
                                : copy.tileReserved
                        }`}
                        onClick={() => pick(tile.id)}
                    >
                        {tile.label}
                    </button>
                ))}
            </div>

            <div className="op-ctas">
                <button
                    type="button"
                    className="op-cta op-cta-1"
                    disabled={purchasesPaused || register.available === 0}
                    onClick={reserve}
                >
                    {copy.chooseNumber}
                </button>

                <button
                    type="button"
                    className="op-cta op-cta-2"
                    onClick={onLeave}
                >
                    {copy.enterHouse}
                </button>
            </div>
        </section>
    );
}
