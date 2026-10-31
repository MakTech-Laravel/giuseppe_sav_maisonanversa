import { useTranslation } from 'react-i18next';
import { OpeningEditionCopy } from '@/components/maison/home/opening/opening-edition-copy';
import { OpeningGreeting } from '@/components/maison/home/opening/opening-greeting';
import { OpeningHouse } from '@/components/maison/home/opening/opening-house';
import { OpeningRegister } from '@/components/maison/home/opening/opening-register';
import { OpeningWordmark } from '@/components/maison/home/opening/opening-wordmark';
import { useHomeOpening } from '@/components/maison/home/opening/use-home-opening';
import { useResumePendingOpeningClaim } from '@/hooks/use-resume-pending-opening-claim';
import type { OpeningRegister as OpeningRegisterData } from '@/types/opening-register';

/**
 * Homepage opening: SVG door sequence + live register (Phase 2).
 */
export function HomeOpening({
    openingRegister,
}: {
    openingRegister: OpeningRegisterData;
}) {
    const { t } = useTranslation();
    const { rootRef, className, onHeroClick } = useHomeOpening();

    useResumePendingOpeningClaim(openingRegister);

    return (
        <div ref={rootRef} className={className}>
            <section
                className="home-opening-hero"
                id="opening-hero"
                aria-label={t('Maison Anversa opening')}
                onClick={onHeroClick}
            >
                <OpeningGreeting />
                <OpeningHouse />
                <OpeningWordmark />
                <OpeningEditionCopy
                    productName={openingRegister.product.productName}
                    editionTotal={openingRegister.product.editionTotal}
                    availableCount={openingRegister.available_count}
                />
            </section>

            <OpeningRegister openingRegister={openingRegister} />
        </div>
    );
}
