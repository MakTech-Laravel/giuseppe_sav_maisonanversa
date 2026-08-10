import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import type { Edition } from '@/types/edition';

/**
 * The full-bleed arrival: mansion atmosphere, the wordmark, three doorways and
 * the stock counter in the corner. Height is the viewport minus the shell, so
 * the first screen reads as one composition rather than a dashboard.
 */
export function HomeHero({ edition }: { edition: Edition }) {
    const { t } = useTranslation();
    const { openNewsletter } = useShellActions();

    return (
        <section className="relative flex min-h-[calc(100vh-var(--topbar-h)-var(--nav-h))] items-end overflow-hidden bg-choc">
            <div
                aria-hidden="true"
                className="absolute inset-0"
                style={{
                    backgroundImage: [
                        'linear-gradient(158deg, rgba(41,28,24,0.45) 0%, rgba(41,28,24,0.25) 35%, rgba(41,28,24,0.75) 70%, rgba(10,7,4,0.95) 100%)',
                        'radial-gradient(ellipse at 68% 28%, rgba(100,68,40,0.30) 0%, transparent 42%)',
                        'radial-gradient(ellipse at 20% 70%, rgba(35,22,12,0.55) 0%, transparent 50%)',
                    ].join(', '),
                }}
            >
                <PlaceholderImage
                    asset="hero-mansion"
                    ratio={null}
                    alt=""
                    captioned={false}
                    loading="eager"
                    className="absolute inset-0 -z-10 h-full w-full opacity-70"
                />
            </div>

            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.13]"
            >
                <PlaceholderImage
                    asset="maison-facade-house"
                    ratio={null}
                    alt=""
                    captioned={false}
                    className="w-[min(620px,86vw)] mix-blend-screen"
                />
            </div>

            <div
                aria-hidden="true"
                className="absolute inset-0 bg-linear-to-b from-choc/20 via-choc/5 via-30% to-choc/95"
            />

            <div className="relative z-2 mx-auto w-full max-w-320 px-8 pb-16 md:px-20 md:pb-20">
                <p className="mb-5 flex items-center gap-3.5 font-sans text-[9px] font-light tracking-[0.4em] text-gold uppercase before:inline-block before:h-px before:w-6 before:bg-gold before:content-['']">
                    {t('Antwerpen, België — Founding Edition 2026')}
                </p>

                <h1 className="font-serif text-[clamp(52px,7vw,92px)] leading-none font-normal tracking-[0.1em] text-cream uppercase">
                    Maison
                    <span className="mt-1 block text-[clamp(30px,4vw,56px)] font-light tracking-[0.2em] text-cream/60">
                        Anversa
                    </span>
                </h1>

                <div className="my-6 h-px w-12 bg-gold" />

                <p className="mb-10 font-sans text-[10px] font-light tracking-[0.35em] text-gold uppercase">
                    {t(
                        'European Heritage Sports and Lifestyle House · Gebouwd voor generaties.',
                    )}
                </p>

                <div className="flex flex-wrap items-center gap-5">
                    <MaisonButton as={MaisonLink} variant="hero" to="product">
                        {t('Ontdek Heritage No.001 →')}
                    </MaisonButton>
                    <MaisonButton as={MaisonLink} variant="ghost" to="house">
                        {t('Betreed het Huis')}
                    </MaisonButton>
                    <MaisonButton variant="ghost" onClick={openNewsletter}>
                        {t('Heritage Letter')}
                    </MaisonButton>
                </div>
            </div>

            <div className="absolute right-8 bottom-16 z-2 hidden text-right max-[480px]:hidden sm:block md:right-20 md:bottom-20">
                <div className="font-serif text-[72px] leading-none font-light text-gold">
                    {String(edition.available).padStart(2, '0')}
                </div>
                <div className="my-2 ml-auto h-px w-8 bg-gold/30" />
                <div className="font-sans text-[8px] font-light tracking-[0.3em] text-sand uppercase">
                    {t('Nummers nog')}
                    <br />
                    {t('beschikbaar')}
                </div>
            </div>
        </section>
    );
}
