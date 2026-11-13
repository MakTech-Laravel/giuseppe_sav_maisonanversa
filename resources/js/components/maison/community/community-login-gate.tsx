import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Section, Wrap } from '@/components/maison/ui/section';
import { useLocale } from '@/hooks/use-locale';
import { foundingProductUrl } from '@/lib/maison-navigation';

/**
 * Gate for guests. Opens the real Maison auth modal — Community access
 * requires a signed-in account (customer or staff).
 */
export function CommunityLoginGate() {
    const { t } = useTranslation();
    const { openAuth } = useShellActions();
    const { locale } = useLocale();

    return (
        <Section tone="cream" padded className="py-30 text-center">
            <Wrap className="max-w-120">
                <div className="mx-auto mb-6 flex size-16 items-center justify-center overflow-hidden border border-gold">
                    <PlaceholderImage
                        asset="logo-icon"
                        ratio="1 / 1"
                        alt="Maison Anversa"
                        captioned={false}
                        className="size-full"
                    />
                </div>

                <h2 className="mb-3 font-serif text-4xl font-medium text-choc [&_em]:text-gold2 [&_em]:italic">
                    {t('Welcome')}
                    <br />
                    {t('back,')} <em>{t('member')}</em>
                </h2>

                <p className="mb-9 text-base leading-[1.8] text-choc3">
                    {t(
                        'The Community is exclusive to signed-in members. Sign in to get access.',
                    )}
                </p>
                <ul className="mb-9 space-y-2 text-left text-sm leading-[1.7] text-choc3">
                    <li>
                        {t('Plan padel sessions with other members of the house.')}
                    </li>
                    <li>
                        {t('Discover exclusive Maison Anversa events.')}
                    </li>
                    <li>
                        {t(
                            'Browse padel and tennis clubs where members plan sessions.',
                        )}
                    </li>
                </ul>

                <MaisonButton
                    variant="filled"
                    block
                    type="button"
                    onClick={() => openAuth('login')}
                >
                    {t('Log in')}
                </MaisonButton>

                <div className="my-5 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                    {t('or')}
                </div>

                <p className="mb-4 text-sm text-choc3">
                    {t(
                        'Not a member yet? Buy Heritage Nº 001 and automatically become a Founding Member.',
                    )}
                </p>

                <MaisonLink
                    href={foundingProductUrl(locale)}
                    className="inline-block font-sans text-[10px] tracking-[0.2em] text-choc3 uppercase underline-offset-4 hover:text-choc hover:underline"
                >
                    {t('View Heritage Nº 001 →')}
                </MaisonLink>
            </Wrap>
        </Section>
    );
}
