import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Section, Wrap } from '@/components/maison/ui/section';

/**
 * Gate for guests. Opens the real Maison auth modal — Community access
 * requires a signed-in account (customer or staff).
 */
export function CommunityLoginGate() {
    const { t } = useTranslation();
    const { openAuth } = useShellActions();

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
                    {t('Welkom')}
                    <br />
                    {t('terug,')} <em>{t('lid')}</em>
                </h2>

                <p className="mb-9 text-base leading-[1.8] text-choc3">
                    {t(
                        'De Community is exclusief voor Founding Circle leden en Club Corner partners. Log in om toegang te krijgen.',
                    )}
                </p>
                <ul className="mb-9 space-y-2 text-left text-sm leading-[1.7] text-choc3">
                    <li>
                        {t(
                            'Plan padelsessies met andere leden van het huis.',
                        )}
                    </li>
                    <li>
                        {t(
                            'Ontdek exclusieve evenementen van Maison Anversa.',
                        )}
                    </li>
                    <li>
                        {t(
                            'Vind de courts en clubs waar het huis aanwezig is.',
                        )}
                    </li>
                </ul>

                <MaisonButton
                    variant="filled"
                    block
                    type="button"
                    onClick={() => openAuth('login')}
                >
                    {t('Inloggen')}
                </MaisonButton>

                <div className="my-5 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
                    {t('of')}
                </div>

                <p className="mb-4 text-sm text-choc3">
                    {t(
                        'Nog geen lid? Koop Heritage No.001 en word automatisch Founding Member.',
                    )}
                </p>

                <MaisonLink
                    to="product"
                    className="inline-block font-sans text-[10px] tracking-[0.2em] text-choc3 uppercase underline-offset-4 hover:text-choc hover:underline"
                >
                    {t('Bekijk Heritage No.001 →')}
                </MaisonLink>
            </Wrap>
        </Section>
    );
}
