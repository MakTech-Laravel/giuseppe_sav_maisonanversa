import { useTranslation } from 'react-i18next';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Reveal } from '@/components/maison/ui/reveal';

type MaterialCard = {
    num: string;
    name: string;
    desc: string;
};

export function ProductCraft({
    materials = [],
}: {
    materials?: MaterialCard[];
}) {
    const { t } = useTranslation();

    if (materials.length === 0) {
        return null;
    }

    return (
        <section className="bg-choc text-cream">
            <div className="grid md:grid-cols-2">
                <PlaceholderImage
                    asset="atelier-workshop"
                    ratio={null}
                    alt={t('Vakmanschap')}
                    captioned={false}
                    overlay="linear-gradient(to top, rgba(41,28,24,0.4), transparent)"
                    className="min-h-[300px] md:min-h-[440px]"
                />

                <Reveal className="flex flex-col justify-center px-8 py-13 md:px-16 md:py-20">
                    <Eyebrow>{t('Vakmanschap')}</Eyebrow>
                    <GoldRule />
                    <h2 className="mb-5.5 font-serif text-[clamp(30px,3.4vw,46px)] leading-[1.1] font-normal tracking-[0.03em] uppercase">
                        {t('Elk detail')}
                        <br />
                        {t('met opzet.')}
                    </h2>
                    <p className="text-[15px] leading-[1.85] text-sand">
                        {t(
                            'Heritage No.001 wordt gebouwd met materialen die zelden in padel voorkomen. Niet voor de show — voor hoe het voelt in de hand en hoe het veroudert door de jaren.',
                        )}
                    </p>

                    <div className="mt-10 grid grid-cols-1 gap-0.5 bg-gold/15 sm:grid-cols-2">
                        {materials.map((item) => (
                            <div
                                key={item.num}
                                className="bg-choc2 px-6.5 py-7.5"
                            >
                                <div className="font-serif text-[13px] tracking-[0.2em] text-gold">
                                    {item.num}
                                </div>
                                <div className="my-2 font-serif text-[21px] text-cream">
                                    {t(item.name)}
                                </div>
                                <div className="text-[13px] leading-[1.7] text-sand">
                                    {t(item.desc)}
                                </div>
                            </div>
                        ))}
                    </div>
                </Reveal>
            </div>
        </section>
    );
}
