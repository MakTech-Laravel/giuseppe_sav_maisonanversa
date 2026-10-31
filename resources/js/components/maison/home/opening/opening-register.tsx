import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { OpeningRegisterCtas } from '@/components/maison/home/opening/opening-register-ctas';
import { OpeningRegisterGrid } from '@/components/maison/home/opening/opening-register-grid';
import type { OpeningRegister as OpeningRegisterData } from '@/types/opening-register';

type OpeningRegisterProps = {
    openingRegister: OpeningRegisterData;
};

export function OpeningRegister({ openingRegister }: OpeningRegisterProps) {
    const { t } = useTranslation();
    const [selected, setSelected] = useState<number | null>(null);
    const selectedPlace =
        selected === null
            ? null
            : (openingRegister.places.find((place) => place.sequence === selected) ??
              null);

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
                {t('{{count}} nummers beschikbaar', {
                    count: openingRegister.available_count,
                })}
            </p>

            <OpeningRegisterGrid
                places={openingRegister.places}
                selected={selected}
                onSelect={setSelected}
            />

            <p
                className="opening-picked"
                hidden={selectedPlace === null}
                aria-live="polite"
            >
                {selectedPlace
                    ? t('Nº {{n}}: van jou, als je het neemt.', {
                          n: selectedPlace.label,
                      })
                    : null}
            </p>

            <OpeningRegisterCtas
                selected={selected}
                openingRegister={openingRegister}
            />
        </section>
    );
}
