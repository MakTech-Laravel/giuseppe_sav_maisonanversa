import { useTranslation } from 'react-i18next';
import { PlaceholderImage } from '@/components/maison/placeholder-image';

/**
 * The full-width Antwerp etching below the footer, faded into the chocolate at
 * both edges so it reads as an impression rather than a photograph.
 */
export function EtchingBand() {
    const { t } = useTranslation();

    return (
        <div className="relative h-45 overflow-hidden border-t border-gold/14 bg-choc ma-sm:h-57.5">
            <PlaceholderImage
                asset="antwerp-ets-band"
                alt=""
                captioned={false}
                ratio={null}
                className="absolute inset-0 opacity-42"
            />

            <div
                aria-hidden="true"
                className="absolute inset-0 bg-[linear-gradient(to_bottom,var(--color-choc)_0%,rgba(41,28,24,0.15)_32%,rgba(41,28,24,0.15)_68%,var(--color-choc)_100%)]"
            />

            <div className="relative z-2 flex h-full flex-col items-center justify-center gap-2.5 px-6 text-center">
                <span className="font-sans text-[9px] tracking-[0.35em] text-gold uppercase">
                    {t('Anversa · Antwerpen')}
                </span>

                <span
                    aria-hidden="true"
                    className="block h-5.5 w-px bg-gold/50"
                />

                {/*
                 * The dictionary is keyed per text node, because the prototype's
                 * translator walked the DOM node by node — so the emphasised word
                 * is its own entry rather than markup inside one sentence.
                 */}
                <h3 className="font-serif text-[clamp(22px,2.4vw,30px)] tracking-[0.06em] text-cream">
                    {t('De stad die ons')}{' '}
                    <em className="text-gold">{t('draagt')}</em>.
                </h3>
            </div>
        </div>
    );
}
