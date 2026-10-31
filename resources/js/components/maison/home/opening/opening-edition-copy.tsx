import { useTranslation } from 'react-i18next';

type OpeningEditionCopyProps = {
    productName: string;
    editionTotal: number;
    availableCount: number;
};

export function OpeningEditionCopy({
    productName,
    editionTotal,
    availableCount,
}: OpeningEditionCopyProps) {
    const { t } = useTranslation();

    return (
        <div className="ma-in ma-t2 opening-edition">
            <p className="edition">
                {productName || 'Heritage Nº 001 · Founding Edition'}
            </p>
            <p className="limit">
                {t(
                    'Beperkt tot {{total}} genummerde rackets · nog {{count}} nummers vrij',
                    { total: editionTotal, count: availableCount },
                )}
            </p>
        </div>
    );
}
