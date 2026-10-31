import { useTranslation } from 'react-i18next';

function pad(n: number): string {
    return String(n).padStart(3, '0');
}

type OpeningRegisterCtasProps = {
    selected: number | null;
};

export function OpeningRegisterCtas({ selected }: OpeningRegisterCtasProps) {
    const { t } = useTranslation();

    const claimLabel = selected
        ? t('Neem Nº {{n}}', { n: pad(selected) })
        : t('Kies je nummer');

    return (
        <div className="opening-ctas">
            <button
                type="button"
                className="opening-cta opening-cta-1"
                aria-disabled={selected !== null}
                title={
                    selected
                        ? t('Reserveren wordt binnenkort beschikbaar')
                        : undefined
                }
                onClick={() => {
                    if (selected !== null) {
                        // Phase 2: hold + openPurchase with preselected number.
                        return;
                    }

                    document
                        .getElementById('opening-register')
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start',
                        });
                }}
            >
                {claimLabel}
            </button>
            <a className="opening-cta opening-cta-2" href="#home-hero">
                {t('Kom binnen')}
            </a>
        </div>
    );
}
