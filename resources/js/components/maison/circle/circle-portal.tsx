import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { useLocale } from '@/hooks/use-locale';

/**
 * Points Founding Circle visitors to the authenticated member card.
 */
export function CirclePortal() {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const { auth } = usePage().props;
    const { openAuth } = useShellActions();

    if (auth.user) {
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
                {t('Founding Circle')}
            </p>
            <p className="mt-3 text-sm text-choc3">
                {t(
                    'Log in om uw digitale kaart te zien, of reserveer Heritage No.001 om lid te worden.',
                )}
            </p>
            <div className="mt-6">
                <MaisonButton variant="choc" onClick={() => openAuth('login')}>
                    {t('Inloggen')}
                </MaisonButton>
            </div>
        </div>
    );
}
