import { useState } from 'react';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { usePurchasesPaused } from '@/hooks/use-purchases-paused';
import {
    OPENING_EDITION_SIZE,
    OPENING_RESERVED_NUMBERS,
    padNumber,
} from './opening-data';
import type { OpeningCopy } from './opening-data';

type OpeningRegisterProps = {
    copy: OpeningCopy;
    /** The visitor is leaving the opening, for the home page or for the reservation. */
    onLeave: () => void;
};

/**
 * The register: the hundred numbered rackets as tiles, under the house, and the
 * two ways out of the opening.
 *
 * - "Choose your number" starts the site's own reservation: the shell's
 *   `openPurchase()`, which asks a visitor who is not signed in to log in, then
 *   opens the edition picker (the real, live list of numbers) and the order modal.
 *   It leaves the opening first, because those modals sit below it.
 * - "Enter the house" leaves the opening for the home page behind it.
 *
 * The tiles are static for now (the reserved numbers are a placeholder until the
 * live register is wired), so a tile highlights but does not pre-select a number
 * in the picker: that needs the real edition piece, which arrives with the data.
 */
export function OpeningRegister({ copy, onLeave }: OpeningRegisterProps) {
    const { openPurchase } = useShellActions();
    const purchasesPaused = usePurchasesPaused();
    const [selected, setSelected] = useState<number | null>(null);

    const numbers = Array.from(
        { length: OPENING_EDITION_SIZE },
        (_, index) => index + 1,
    );

    function pick(value: number) {
        setSelected((current) => (current === value ? null : value));
    }

    function reserve() {
        onLeave();
        openPurchase();
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

            <div className="op-numbers">
                {numbers.map((value) => {
                    const reserved = OPENING_RESERVED_NUMBERS.includes(value);

                    return (
                        <button
                            key={value}
                            type="button"
                            className="op-num"
                            disabled={reserved}
                            aria-pressed={selected === value}
                            aria-label={`Nº ${padNumber(value)}, ${
                                reserved
                                    ? copy.tileReserved
                                    : copy.tileAvailable
                            }`}
                            onClick={() => pick(value)}
                        >
                            {padNumber(value)}
                        </button>
                    );
                })}
            </div>

            <div className="op-ctas">
                <button
                    type="button"
                    className="op-cta op-cta-1"
                    disabled={purchasesPaused}
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
