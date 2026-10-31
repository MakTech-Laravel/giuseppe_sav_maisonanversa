import { router, useHttp, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { hold } from '@/actions/App/Http/Controllers/Maison/ProductEditionController';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { useLocale } from '@/hooks/use-locale';
import { writePendingOpeningClaim } from '@/lib/pending-opening-claim';
import type { OpeningRegister } from '@/types/opening-register';

type OpeningRegisterCtasProps = {
    selected: number | null;
    openingRegister: OpeningRegister;
};

export function OpeningRegisterCtas({
    selected,
    openingRegister,
}: OpeningRegisterCtasProps) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const { auth } = usePage().props;
    const { openAuth, openPurchase } = useShellActions();
    const { submit } = useHttp();
    const [claiming, setClaiming] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const place =
        selected === null
            ? null
            : (openingRegister.places.find((row) => row.sequence === selected) ??
              null);

    const claimLabel = place
        ? t('Neem Nº {{n}}', { n: place.label })
        : t('Kies je nummer');

    const unavailableMessage = t(
        'Dit editienummer is niet meer beschikbaar. Kies een ander nummer.',
    );

    function refreshRegister(): void {
        router.reload({ only: ['openingRegister'] });
    }

    async function claimSelected(): Promise<void> {
        if (!place || place.edition_piece_id === null || claiming) {
            return;
        }

        const productSlug = openingRegister.product.productSlug;

        if (!productSlug) {
            setError(t('Dit product is momenteel niet beschikbaar.'));

            return;
        }

        const claim = {
            productSlug,
            editionPieceId: place.edition_piece_id,
            editionNumber: place.sequence,
            editionLabel: place.label,
        };

        if (!auth?.user) {
            writePendingOpeningClaim(claim);
            openAuth('login', { forceHome: true });

            return;
        }

        if (auth.user.is_admin) {
            setError(
                t(
                    'Staff accounts cannot claim an edition. Sign in as a customer to purchase.',
                ),
            );

            return;
        }

        setClaiming(true);
        setError(null);

        let claimFailed = false;

        try {
            await submit(
                hold({
                    locale,
                    product: productSlug,
                    editionPiece: place.edition_piece_id,
                }),
                {
                    onError: (errors) => {
                        claimFailed = true;
                        const message = errors.edition_piece_id;
                        setError(
                            typeof message === 'string' && message !== ''
                                ? message
                                : unavailableMessage,
                        );
                        refreshRegister();
                    },
                    onHttpException: (response) => {
                        claimFailed = true;

                        if (response.status === 403) {
                            setError(
                                t(
                                    'Staff accounts cannot claim an edition. Sign in as a customer to purchase.',
                                ),
                            );

                            return;
                        }

                        if (response.status === 429) {
                            setError(
                                t(
                                    'Too many attempts. Please wait a moment and try again.',
                                ),
                            );

                            return;
                        }

                        setError(unavailableMessage);
                        refreshRegister();
                    },
                },
            );

            if (claimFailed) {
                return;
            }

            openPurchase({
                productId: openingRegister.product.productId,
                productSlug,
                currency: openingRegister.product.currency,
                amount: openingRegister.product.amount,
                displayAmount: openingRegister.product.displayAmount,
                productName: openingRegister.product.productName,
                deliveryLabel: openingRegister.product.deliveryLabel,
                productType:
                    openingRegister.product.productType ?? 'limited_edition',
                editionPieceId: place.edition_piece_id,
                editionNumber: place.sequence,
                editionLabel: place.label,
            });
        } catch {
            if (!claimFailed) {
                setError(unavailableMessage);
                refreshRegister();
            }
        } finally {
            setClaiming(false);
        }
    }

    return (
        <div className="opening-ctas">
            <button
                type="button"
                className="opening-cta opening-cta-1"
                disabled={claiming}
                onClick={() => {
                    if (selected === null || !place) {
                        document
                            .getElementById('opening-register')
                            ?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start',
                            });

                        return;
                    }

                    void claimSelected();
                }}
            >
                {claiming ? t('Laden…') : claimLabel}
            </button>
            <a className="opening-cta opening-cta-2" href="#home-hero">
                {t('Kom binnen')}
            </a>
            {error ? (
                <p className="w-full text-sm text-red-800" role="alert">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
