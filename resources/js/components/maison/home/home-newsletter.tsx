import { useTranslation } from 'react-i18next';
import { HeritageLetterForm } from '@/components/maison/heritage-letter-form';
import { Eyebrow } from '@/components/maison/ui/eyebrow';

export function HomeNewsletter() {
    const { t } = useTranslation();

    return (
        <div
            id="nl-home"
            className="grid items-center gap-10 border-t border-gold/15 bg-choc px-8 py-18 text-cream md:grid-cols-2 md:gap-15 md:px-20"
        >
            <div>
                <Eyebrow>{t('De Heritage Letter')}</Eyebrow>
                <h2 className="mt-3 mb-2.5 font-serif text-[clamp(24px,2.8vw,36px)] leading-[1.2] font-medium tracking-[0.06em] uppercase">
                    {t('Word lid van')}
                    <br />
                    {t('de Heritage Letter')}
                </h2>
                <p className="text-[14px] leading-[1.8] text-sand">
                    {t(
                        'Als eerste verhalen, nieuws en exclusieve uitnodigingen ontvangen van Maison Anversa.',
                    )}
                </p>
            </div>

            <HeritageLetterForm source="home" />
        </div>
    );
}
