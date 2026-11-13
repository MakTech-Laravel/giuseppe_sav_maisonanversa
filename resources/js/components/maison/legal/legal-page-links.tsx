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
                {t('Contact us via')}{' '}
                <LegalChromeLink to="contact" preview={preview}>
                    {t('our contact page')}
                </LegalChromeLink>
                .
            </p>
        );
    }

    if (slug === 'terms') {
        return (
            <p>
                {t('See also the')}{' '}
                <LegalChromeLink to="care" preview={preview}>
                    {t('care and warranty policy')}
                </LegalChromeLink>{' '}
                {t('and the')}{' '}
                <LegalChromeLink to="shipping" preview={preview}>
                    {t('shipping and return policy')}
                </LegalChromeLink>
                .
            </p>
        );
    }

    if (slug === 'care' || slug === 'shipping') {
        return (
            <p>
                {t('Questions? Visit the')}{' '}
                <LegalChromeLink to="contact" preview={preview}>
                    {t('contact page')}
                </LegalChromeLink>
                .
            </p>
        );
    }

    return null;
}
