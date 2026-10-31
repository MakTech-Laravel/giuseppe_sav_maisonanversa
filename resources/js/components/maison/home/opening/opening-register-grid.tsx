import { useTranslation } from 'react-i18next';

function pad(n: number): string {
    return String(n).padStart(3, '0');
}

type OpeningRegisterGridProps = {
    reserved: ReadonlySet<number>;
    selected: number | null;
    onSelect: (n: number | null) => void;
};

export function OpeningRegisterGrid({
    reserved,
    selected,
    onSelect,
}: OpeningRegisterGridProps) {
    const { t } = useTranslation();

    return (
        <div className="opening-grid" id="opening-grid" role="group">
            {Array.from({ length: 100 }, (_, index) => {
                const n = index + 1;
                const isReserved = reserved.has(n);
                const isSelected = selected === n;
                const label = pad(n);

                return (
                    <button
                        key={n}
                        type="button"
                        className="opening-num"
                        disabled={isReserved}
                        aria-pressed={isSelected}
                        aria-label={t(
                            isReserved
                                ? 'Nº {{n}}, gereserveerd'
                                : 'Nº {{n}}, beschikbaar',
                            { n: label },
                        )}
                        onClick={() => {
                            onSelect(isSelected ? null : n);
                        }}
                    >
                        {label}
                    </button>
                );
            })}
        </div>
    );
}
