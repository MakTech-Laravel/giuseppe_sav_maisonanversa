import { useTranslation } from 'react-i18next';
import { OpeningEditionCopy } from '@/components/maison/home/opening/opening-edition-copy';
import { OpeningGreeting } from '@/components/maison/home/opening/opening-greeting';
import { OpeningHouse } from '@/components/maison/home/opening/opening-house';
import {
    OpeningRegister,
    openingAvailableCount,
} from '@/components/maison/home/opening/opening-register';
import { OpeningWordmark } from '@/components/maison/home/opening/opening-wordmark';
import { useHomeOpening } from '@/components/maison/home/opening/use-home-opening';

/**
 * Homepage opening: SVG door sequence + static register (Phase 1).
 * Ported from new_home_page/opening-reference.html.
 */
export function HomeOpening() {
    const { t } = useTranslation();
    const { rootRef, className, onHeroClick } = useHomeOpening();

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
                <OpeningEditionCopy availableCount={openingAvailableCount()} />
            </section>

            <OpeningRegister />
        </div>
    );
}
