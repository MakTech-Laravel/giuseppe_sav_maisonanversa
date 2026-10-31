import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { OpeningRegisterCtas } from '@/components/maison/home/opening/opening-register-ctas';
import { OpeningRegisterGrid } from '@/components/maison/home/opening/opening-register-grid';

/** Phase 1 static reserved numbers — replaced by live inventory in Phase 2. */
const STATIC_RESERVED = [1, 2] as const;

function pad(n: number): string {
    return String(n).padStart(3, '0');
}

export function OpeningRegister() {
    const { t } = useTranslation();
    const reserved = useMemo(() => new Set(STATIC_RESERVED), []);
    const [selected, setSelected] = useState<number | null>(null);
    const availableCount = 100 - reserved.size;

    return (
        <section
            className="opening-register ma-in ma-t3"
            id="opening-register"
            aria-labelledby="opening-reg-title"
        >
            <div className="opening-reg-head">
                <h2 id="opening-reg-title">{t('Het register')}</h2>
                <p className="opening-legend" aria-hidden="true">
                    <span>
                        <i className="opening-sw opening-sw-a" />
                        {t('Vrij')}
                    </span>
                    <span>
                        <i className="opening-sw opening-sw-r" />
                        {t('Gereserveerd')}
                    </span>
                </p>
            </div>

            <p className="sr-only">
                {t('{{count}} nummers beschikbaar', { count: availableCount })}
            </p>

            <OpeningRegisterGrid
                reserved={reserved}
                selected={selected}
                onSelect={setSelected}
            />

            <p
                className="opening-picked"
                hidden={selected === null}
                aria-live="polite"
            >
                {selected
                    ? t('Nº {{n}}: van jou, als je het neemt.', {
                          n: pad(selected),
                      })
                    : null}
            </p>

            <OpeningRegisterCtas selected={selected} />
        </section>
    );
}

export function openingAvailableCount(): number {
    return 100 - STATIC_RESERVED.length;
}
