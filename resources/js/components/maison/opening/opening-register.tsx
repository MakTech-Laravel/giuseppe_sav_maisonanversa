import { useState } from 'react';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { usePurchasesPaused } from '@/hooks/use-purchases-paused';
import { cn } from '@/lib/utils';
import type { OpeningRegisterData } from '@/types/opening';
import { fillCopy, OPENING_PAGE_SIZE } from './opening-data';
import type { OpeningCopy } from './opening-data';

/**
 * The page buttons to show: every page up to seven, else the first, the last and
 * the pages around the current one, with `null` standing for a gap ("…").
 *
 * @returns zero-based page numbers
 */
function pageItems(current: number, pages: number): (number | null)[] {
    if (pages <= 7) {
        return Array.from({ length: pages }, (_, index) => index);
    }

    const shown = new Set([0, pages - 1, current - 1, current, current + 1]);
    const sorted = [...shown]
        .filter((index) => index >= 0 && index < pages)
        .sort((a, b) => a - b);

    return sorted.flatMap((index, position) =>
        position > 0 && index - sorted[position - 1] > 1
            ? [null, index]
            : [index],
    );
}

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
 * - Available numbers print as available; archived numbers (Maison Archive) have
 *   their own mark; every other held, sold or returning number prints as reserved.
 * - "Choose your number" starts the site's own reservation for that product: the
 *   shell's `openPurchase()`, which asks a visitor who is not signed in to log in,
 *   then opens the edition picker (the live list of numbers) and the order modal.
 *   It leaves the opening first, because those modals sit below it.
 * - "Enter the house" leaves the opening for the home page behind it.
 *
 * Everything sits in one centred column of 600 px: title, legend, grid, the chosen
 * number and the two buttons. The tiles sit in a fixed grid, ten per row on every
 * screen (compact on a phone, so all 100 fit on one screen).
 * An edition of more than OPENING_PAGE_SIZE (100) numbers is paged, one 10 × 10 grid
 * per page; 100 or fewer shows no pager. Every tile stays in `register.tiles`, so a
 * chosen number survives a change of page and still reaches the picker.
 * A tile highlights. If one is chosen when "Choose your number" is pressed, it is
 * handed to the picker, which holds it with its normal hold request and goes on to
 * the order modal without asking again. With no tile chosen, the picker lists them.
 */
export function OpeningRegister({
    copy,
    register,
    onLeave,
}: OpeningRegisterProps) {
    const { openPurchase, openNewsletter } = useShellActions();
    const purchasesPaused = usePurchasesPaused();
    const [selected, setSelected] = useState<number | null>(null);
    const [page, setPage] = useState(0);

    // Paged only above one page: 100 numbers or fewer are shown whole, no pager.
    const paged = register.tiles.length > OPENING_PAGE_SIZE;
    const pages = Math.ceil(register.tiles.length / OPENING_PAGE_SIZE);
    // Clamped, so an edition that shrinks never leaves the visitor on an empty page.
    const currentPage = Math.min(page, Math.max(pages - 1, 0));
    const firstOnPage = paged ? currentPage * OPENING_PAGE_SIZE : 0;
    const visibleTiles = paged
        ? register.tiles.slice(firstOnPage, firstOnPage + OPENING_PAGE_SIZE)
        : register.tiles;

    function pick(id: number) {
        setSelected((current) => (current === id ? null : id));
    }

    const pickedTile = register.tiles.find(
        (entry) => entry.id === selected && entry.available,
    );

    function reserve() {
        onLeave();

        // Before launch the button joins the list instead.
        if (purchasesPaused) {
            openNewsletter();

            return;
        }

        const tile = register.tiles.find((entry) => entry.id === selected);

        openPurchase(
            register.product,
            tile?.available
                ? { editionPieceId: tile.id, editionLabel: tile.label }
                : undefined,
        );
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
                    <span>
                        <i className="op-sw op-sw-archive" />
                        {copy.archive}
                    </span>
                </div>
            </div>

            <div className="op-numbers">
                {visibleTiles.map((tile) => {
                    const isArchive = tile.status === 'archive';
                    const statusLabel = isArchive
                        ? copy.tileArchive
                        : tile.available
                          ? copy.tileAvailable
                          : copy.tileReserved;

                    return (
                        <button
                            key={tile.id}
                            type="button"
                            className={cn(
                                'op-num',
                                isArchive && 'op-num-archive',
                                tile.label.length > 3 && 'op-num-long',
                            )}
                            disabled={!tile.available}
                            aria-pressed={selected === tile.id}
                            title={isArchive ? copy.archiveTooltip : undefined}
                            aria-label={`Nº ${tile.label}, ${statusLabel}`}
                            onClick={() => pick(tile.id)}
                        >
                            {tile.label}
                        </button>
                    );
                })}
            </div>

            {paged ? (
                <nav className="op-pager" aria-label={copy.pagination}>
                    <button
                        type="button"
                        className="op-page op-page-step"
                        disabled={currentPage === 0}
                        onClick={() => setPage(currentPage - 1)}
                    >
                        ← {copy.previousPage}
                    </button>

                    {pageItems(currentPage, pages).map((item, index) =>
                        item === null ? (
                            <span
                                key={`gap-${index}`}
                                className="op-page-gap"
                                aria-hidden="true"
                            >
                                …
                            </span>
                        ) : (
                            <button
                                key={item}
                                type="button"
                                className="op-page op-page-num"
                                aria-label={fillCopy(copy.pageLabel, {
                                    n: item + 1,
                                })}
                                aria-current={
                                    item === currentPage ? 'page' : undefined
                                }
                                onClick={() => setPage(item)}
                            >
                                {item + 1}
                            </button>
                        ),
                    )}

                    <button
                        type="button"
                        className="op-page op-page-step"
                        disabled={currentPage === pages - 1}
                        onClick={() => setPage(currentPage + 1)}
                    >
                        {copy.nextPage} →
                    </button>

                    <p className="op-page-range" aria-live="polite">
                        {fillCopy(copy.numbersRange, {
                            from: firstOnPage + 1,
                            to: firstOnPage + visibleTiles.length,
                            total: register.tiles.length,
                        })}
                    </p>
                </nav>
            ) : null}

            {pickedTile !== undefined ? (
                <p className="op-picked" aria-live="polite">
                    {fillCopy(copy.pickedLine, { n: pickedTile.label })}
                </p>
            ) : null}

            <div className="op-ctas">
                <button
                    type="button"
                    className="op-cta op-cta-1"
                    disabled={!purchasesPaused && register.available === 0}
                    onClick={reserve}
                >
                    {purchasesPaused
                        ? copy.joinList
                        : pickedTile !== undefined
                          ? fillCopy(copy.claimNumber, { n: pickedTile.label })
                          : copy.chooseNumber}
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
