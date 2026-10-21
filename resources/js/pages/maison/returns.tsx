import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageHero } from '@/components/maison/ui/page-hero';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function MaisonReturns({
    returnAddress,
    loginRedirect,
}: {
    returnAddress: string;
    loginRedirect: string;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    /**
     * Always hit the protected member orders URL. Guests get url.intended set
     * by auth middleware, then the home login modal; after login,
     * PostLoginRedirectService honors that intended path.
     */
    function startReturn() {
        window.location.assign(loginRedirect);
    }

    return (
        <>
            <MaisonSeoHead />
            <Head title={t('Retourneren')} />

            <PageHero
                eyebrow={t('Verzending & Retour')}
                title={t('Retourneren binnen 14 dagen')}
                subtitle={t(
                    'Je kunt een retour starten via Mijn account → Bestellingen. De wettelijke bedenktijd begint de dag na levering.',
                )}
            >
                <div className="mx-auto flex max-w-3xl flex-col items-center gap-6 sm:gap-8">
                    <div className="flex flex-wrap items-center justify-center gap-3">
                        <button
                            type="button"
                            onClick={startReturn}
                            className="inline-flex min-h-11 cursor-pointer items-center justify-center border border-cream bg-cream px-6 font-sans text-[10px] tracking-[0.2em] text-choc uppercase"
                        >
                            {t('Start een retour')}
                        </button>
                        <Link
                            href={`/${locale}/shipping`}
                            className="inline-flex min-h-11 items-center justify-center border border-cream/40 px-6 font-sans text-[10px] tracking-[0.2em] text-cream uppercase no-underline"
                        >
                            {t('Verzend- en retourbeleid')}
                        </Link>
                    </div>
                    <aside className="w-full max-w-sm border border-gold/25 bg-choc/40 px-5 py-4 text-center backdrop-blur-sm">
                        <p className="font-sans text-[10px] tracking-[0.2em] text-sand uppercase">
                            {t('Retouradres')}
                        </p>
                        <p className="mt-2 font-sans text-sm leading-relaxed whitespace-pre-line text-cream">
                            {returnAddress}
                        </p>
                    </aside>
                </div>
            </PageHero>
        </>
    );
}
