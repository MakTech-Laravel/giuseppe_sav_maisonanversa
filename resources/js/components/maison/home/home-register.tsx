import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { useLocale } from '@/hooks/use-locale';

export function HomeRegister() {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const { foundingRegister } = usePage().props;
    const total = foundingRegister.places_total || 100;
    const inscribed = foundingRegister.inscribed_count;

    return (
        <section className="bg-choc px-6 py-20 text-cream md:px-16 md:py-24">
            <div className="mx-auto grid max-w-6xl items-center gap-14 md:grid-cols-2 md:gap-16 lg:gap-24">
                <div>
                    <p className="font-sans text-[10px] tracking-[0.28em] text-sand uppercase">
                        {t('Het Founding Circle-register')}
                    </p>
                    <h2 className="mt-5 max-w-xl font-serif text-[clamp(32px,4.2vw,52px)] leading-[1.08] font-normal text-cream">
                        {t('Honderd plaatsen. Eén eerste hoofdstuk.')}
                    </h2>
                    <GoldRule className="my-6 bg-sand/50" />
                    <p className="max-w-md text-[15px] leading-[1.75] text-sand">
                        {t(
                            'Elk lid van de Founding Circle wordt ingeschreven in het officiële register van het Huis, in de volgorde waarin zij toetraden. Zodra alle honderd plaatsen zijn ingenomen, sluit het eerste hoofdstuk voorgoed.',
                        )}
                    </p>
                    <Link
                        href={`/${locale}/founding-circle/register`}
                        className="mt-10 inline-flex min-h-11 items-center border border-cream/70 px-6 font-sans text-[10px] tracking-[0.18em] text-cream uppercase no-underline transition-colors hover:bg-cream hover:text-choc"
                    >
                        {t('Bekijk het register')}
                    </Link>
                </div>

                <div className="flex flex-col items-start md:items-end">
                    <div className="w-full max-w-md md:text-right">
                        <p className="font-serif leading-none">
                            <span className="text-[clamp(56px,8vw,84px)] text-cream">
                                {inscribed}
                            </span>
                            <span className="ml-2 text-[clamp(22px,3vw,32px)] text-sand">
                                / {total}
                            </span>
                        </p>
                        <p className="mt-3 font-sans text-[10px] tracking-[0.22em] text-sand uppercase">
                            {t('Plaatsen ingeschreven')}
                        </p>
                    </div>

                    <div
                        className="mt-8 grid w-full max-w-md grid-cols-10 gap-1.5"
                        aria-hidden="true"
                    >
                        {Array.from({ length: total }, (_, index) => (
                            <span
                                key={index}
                                className={
                                    index < inscribed
                                        ? 'aspect-square w-full bg-cream'
                                        : 'aspect-square w-full border border-sand/45'
                                }
                            />
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
