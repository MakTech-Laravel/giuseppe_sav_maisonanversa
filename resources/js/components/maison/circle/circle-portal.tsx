import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useLocale } from '@/hooks/use-locale';

/**
 * Inscribed members open their card. Everyone else goes to the public register.
 */
export function CirclePortal() {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const { auth } = usePage().props;
    const isFoundingCircle = Boolean(auth?.user?.is_founding_circle);

    if (isFoundingCircle) {
        return (
            <div className="my-12 border border-gold/25 bg-cream2 p-8 text-center">
                <p className="font-serif text-2xl text-choc">
                    {t('Uw Founding Circle-kaart')}
                </p>
                <p className="mt-3 text-sm text-choc3">
                    {t(
                        'Open uw digitale kaart en Heritage Passport in uw account.',
                    )}
                </p>
                <Link
                    href={`/${locale}/member/circle`}
                    className="mt-6 inline-flex border border-gold bg-gold px-6 py-3 font-sans text-[10px] tracking-[0.2em] text-choc uppercase no-underline"
                >
                    {t('Naar mijn kaart')}
                </Link>
            </div>
        );
    }

    return (
        <div className="my-12 border border-gold/25 bg-cream2 p-8 text-center">
            <p className="font-serif text-2xl text-choc">
                {t('Het officiële register')}
            </p>
            <p className="mt-3 text-sm text-choc3">
                {t(
                    'Honderd plaatsen. Bekijk wie het huis vanaf het begin steunt, of kies Heritage No.001 om uw nummer te reserveren.',
                )}
            </p>
            <Link
                href={`/${locale}/founding-circle/register`}
                className="mt-6 inline-flex border border-gold bg-gold px-6 py-3 font-sans text-[10px] tracking-[0.2em] text-choc uppercase no-underline"
            >
                {t('Bekijk het register')}
            </Link>
        </div>
    );
}
