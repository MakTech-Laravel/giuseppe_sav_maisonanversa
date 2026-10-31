import { useTranslation } from 'react-i18next';
import type { OpeningRegisterPlace } from '@/types/opening-register';

type OpeningRegisterGridProps = {
    places: OpeningRegisterPlace[];
    selected: number | null;
    onSelect: (n: number | null) => void;
};

export function OpeningRegisterGrid({
    places,
    selected,
    onSelect,
}: OpeningRegisterGridProps) {
    const { t } = useTranslation();

    return (
        <div className="opening-grid" id="opening-grid" role="group">
            {places.map((place) => {
                const isReserved = place.reserved;
                const isSelected = selected === place.sequence;

                return (
                    <button
                        key={place.sequence}
                        type="button"
                        className="opening-num"
                        disabled={isReserved || place.edition_piece_id === null}
                        aria-pressed={isSelected}
                        aria-label={t(
                            isReserved
                                ? 'Nº {{n}}, gereserveerd'
                                : 'Nº {{n}}, beschikbaar',
                            { n: place.label },
                        )}
                        onClick={() => {
                            onSelect(isSelected ? null : place.sequence);
                        }}
                    >
                        {place.label}
                    </button>
                );
            })}
        </div>
    );
}
