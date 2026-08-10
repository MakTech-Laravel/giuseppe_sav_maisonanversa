import { useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Section, Wrap } from '@/components/maison/ui/section';

type CommunityLoginGateProps = {
    onLogin: () => void;
};

export function CommunityLoginGate({ onLogin }: CommunityLoginGateProps) {
    const { t } = useTranslation();
    const [email, setEmail] = useState('');

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!email.trim()) {
            return;
        }

        onLogin();
    }

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

                <form
                    onSubmit={handleSubmit}
                    className="mb-5 flex flex-col gap-3.5"
                >
                    <input
                        type="email"
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                        placeholder={t('Uw e-mailadres')}
                        className="border border-gold/25 bg-cream2 px-4.5 py-3.5 font-serif text-base text-choc transition-colors outline-none focus:border-gold2"
                    />
                    <input
                        type="password"
                        placeholder={t('Wachtwoord')}
                        className="border border-gold/25 bg-cream2 px-4.5 py-3.5 font-serif text-base text-choc transition-colors outline-none focus:border-gold2"
                    />
                    <MaisonButton variant="filled" block type="submit">
                        {t('Inloggen')}
                    </MaisonButton>
                </form>

                <div className="my-2 font-sans text-[10px] tracking-[0.2em] text-stone uppercase">
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
