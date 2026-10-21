import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useShellActions } from '@/components/maison/shell/shell-actions';
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
    const { auth, locale } = usePage().props;
    const { openAuth } = useShellActions();
    const isAuthed = Boolean(auth?.user);

    function startReturn() {
        if (isAuthed) {
            window.location.assign(loginRedirect);

            return;
        }

        openAuth('login');
        window.sessionStorage.setItem('maison_pending_redirect', loginRedirect);
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
            />

            <section className="bg-cream px-[6vw] pb-24 text-choc">
                <p className="max-w-xl font-sans text-sm text-[#6E5646]">
                    {returnAddress}
                </p>
                <div className="mt-10 flex flex-wrap gap-4">
                    <button
                        type="button"
                        onClick={startReturn}
                        className="inline-flex min-h-11 cursor-pointer items-center justify-center border border-choc bg-choc px-6 font-sans text-[10px] tracking-[0.2em] text-cream uppercase"
                    >
                        {t('Start een retour')}
                    </button>
                    <Link
                        href={`/${locale}/shipping`}
                        className="inline-flex min-h-11 items-center justify-center border border-choc px-6 font-sans text-[10px] tracking-[0.2em] text-choc uppercase no-underline"
                    >
                        {t('Verzend- en retourbeleid')}
                    </Link>
                </div>
            </section>
        </>
    );
}
