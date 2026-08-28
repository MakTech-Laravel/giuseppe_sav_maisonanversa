import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import type { MaisonPage } from '@/lib/maison-navigation';

type LegalPageLinksProps = {
    slug: string;
    preview?: boolean;
};

function LegalChromeLink({
    to,
    preview,
    children,
}: {
    to: MaisonPage;
    preview: boolean;
    children: ReactNode;
}) {
    if (preview) {
        return (
            <span className="underline decoration-gold2 underline-offset-[0.18em]">
                {children}
            </span>
        );
    }

    return <MaisonLink to={to}>{children}</MaisonLink>;
}

export function LegalPageLinks({ slug, preview = false }: LegalPageLinksProps) {
    const { t } = useTranslation();

    if (slug === 'privacy') {
        return (
            <p>
                {t('Contacteer ons via')}{' '}
                <LegalChromeLink to="contact" preview={preview}>
                    {t('onze contactpagina')}
                </LegalChromeLink>
                .
            </p>
        );
    }

    if (slug === 'terms') {
        return (
            <p>
                {t('Zie ook het')}{' '}
                <LegalChromeLink to="care" preview={preview}>
                    {t('zorg- en garantiebeleid')}
                </LegalChromeLink>{' '}
                {t('en het')}{' '}
                <LegalChromeLink to="shipping" preview={preview}>
                    {t('verzend- en retourbeleid')}
                </LegalChromeLink>
                .
            </p>
        );
    }

    if (slug === 'care') {
        return (
            <p>
                {t('Vragen? Bezoek de')}{' '}
                <LegalChromeLink to="contact" preview={preview}>
                    {t('contactpagina')}
                </LegalChromeLink>
                .
            </p>
        );
    }

    return null;
}
