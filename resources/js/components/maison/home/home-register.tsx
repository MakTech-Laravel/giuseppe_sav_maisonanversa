import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useLocale } from '@/hooks/use-locale';

export function HomeRegister() {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const { foundingRegister } = usePage().props;
    const total = foundingRegister.places_total || 100;
    const inscribed = foundingRegister.inscribed_count;

    return (
        <section className="bg-choc px-6 py-16 text-cream md:px-16">
            <div className="mx-auto grid max-w-6xl items-center gap-12 md:grid-cols-[1fr_auto]">
                <div>
                    <p className="font-sans text-[10px] tracking-[0.28em] text-gold uppercase">
                        {t('De Founding Circle')}
                    </p>
                    <h2 className="mt-4 font-serif text-[clamp(32px,4vw,52px)] leading-none">
                        {t('Het officiële register')}
                    </h2>
                    <p className="mt-4 max-w-md text-sm leading-relaxed text-sand">
                        {t(
                            'Honderd plaatsen. Elk nummer dat wordt gekozen, wordt ingeschreven in het register van het huis.',
                        )}
                    </p>
                    <p className="mt-8 font-serif text-5xl text-gold">
                        {inscribed}
                        <span className="text-2xl text-cream"> / {total}</span>
                    </p>
                    <Link
                        href={`/${locale}/founding-circle/register`}
                        className="mt-8 inline-flex min-h-11 items-center border border-gold px-5 font-sans text-[10px] tracking-[0.16em] text-cream uppercase no-underline hover:bg-gold hover:text-choc"
                    >
                        {t('Bekijk het register')}
                    </Link>
                </div>
                <div
                    className="grid grid-cols-10 gap-1"
                    aria-hidden="true"
                >
                    {Array.from({ length: total }, (_, index) => (
                        <span
                            key={index}
                            className={
                                index < inscribed
                                    ? 'size-3 bg-cream md:size-4'
                                    : 'size-3 border border-cream/40 md:size-4'
                            }
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}
