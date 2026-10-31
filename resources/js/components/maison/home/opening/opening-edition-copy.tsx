import { useTranslation } from 'react-i18next';

type OpeningEditionCopyProps = {
    availableCount: number;
};

export function OpeningEditionCopy({
    availableCount,
}: OpeningEditionCopyProps) {
    const { t } = useTranslation();

    return (
        <div className="ma-in ma-t2 opening-edition">
            <p className="edition">Heritage Nº 001 · Founding Edition</p>
            <p className="limit">
                {t(
                    'Beperkt tot 100 genummerde rackets · nog {{count}} nummers vrij',
                    { count: availableCount },
                )}
            </p>
        </div>
    );
}
