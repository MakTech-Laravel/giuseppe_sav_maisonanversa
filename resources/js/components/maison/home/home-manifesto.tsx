import { useTranslation } from 'react-i18next';
import { Wrap } from '@/components/maison/ui/section';

export function HomeManifesto() {
    const { t } = useTranslation();

    return (
        <div className="border-t border-gold/12 bg-choc py-18 text-center text-cream">
            <Wrap>
                <p className="mx-auto max-w-190 font-serif text-[clamp(17px,2vw,22px)] leading-[2.1] text-cream">
                    {t(
                        'Wij bouwen geen sportmerk. Wij bouwen geen lifestylemerk.',
                    )}
                    <br />
                    {t(
                        'Wij bouwen een thuis voor mensen die dezelfde waarden delen.',
                    )}
                    <br />
                    <strong className="font-normal text-gold italic">
                        {t('Wij bouwen Maison Anversa.')}
                    </strong>
                </p>
            </Wrap>
        </div>
    );
}
