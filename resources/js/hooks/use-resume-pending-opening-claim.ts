import { useHttp, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { hold } from '@/actions/App/Http/Controllers/Maison/ProductEditionController';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import {
    clearPendingOpeningClaim,
    readPendingOpeningClaim,
} from '@/lib/pending-opening-claim';
import type { OpeningRegister } from '@/types/opening-register';

/**
 * After a guest Claims a number and logs in, finish hold + openPurchase once.
 */
export function useResumePendingOpeningClaim(
    openingRegister: OpeningRegister,
): void {
    const { auth, locale } = usePage().props;
    const { openPurchase } = useShellActions();
    const { submit } = useHttp();
    const resumed = useRef(false);

    useEffect(() => {
        if (resumed.current || !auth?.user) {
            return;
        }

        if (auth.user.is_admin) {
            clearPendingOpeningClaim();

            return;
        }

        const pending = readPendingOpeningClaim();

        if (pending === null) {
            return;
        }

        const place = openingRegister.places.find(
            (row) =>
                row.edition_piece_id === pending.editionPieceId &&
                !row.reserved,
        );

        if (!place || !openingRegister.product.productSlug) {
            clearPendingOpeningClaim();

            return;
        }

        resumed.current = true;
        clearPendingOpeningClaim();

        void (async () => {
            let failed = false;

            try {
                await submit(
                    hold({
                        locale,
                        product: pending.productSlug,
                        editionPiece: pending.editionPieceId,
                    }),
                    {
                        onError: () => {
                            failed = true;
                        },
                        onHttpException: () => {
                            failed = true;
                        },
                    },
                );

                if (failed) {
                    resumed.current = false;

                    return;
                }

                openPurchase({
                    productId: openingRegister.product.productId,
                    productSlug: openingRegister.product.productSlug,
                    currency: openingRegister.product.currency,
                    amount: openingRegister.product.amount,
                    displayAmount: openingRegister.product.displayAmount,
                    productName: openingRegister.product.productName,
                    deliveryLabel: openingRegister.product.deliveryLabel,
                    productType:
                        openingRegister.product.productType ??
                        'limited_edition',
                    editionPieceId: pending.editionPieceId,
                    editionNumber: pending.editionNumber,
                    editionLabel: pending.editionLabel,
                });
            } catch {
                resumed.current = false;
            }
        })();
    }, [
        auth?.user,
        locale,
        openPurchase,
        openingRegister,
        submit,
    ]);
}
