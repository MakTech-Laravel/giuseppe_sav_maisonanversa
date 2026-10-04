import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import type { Edition } from '@/types/edition';

export type HomeHeroButton = {
    label: string;
    kind: 'link' | 'newsletter';
    href: string | null;
};

export type HomeHeroContent = {
    imageUrl: string | null;
    eyebrow: string;
    title: string;
    titleAccent: string;
    tagline: string;
    showCounter: boolean;
    counterLines: [string, string] | string[];
    buttons: HomeHeroButton[];
};

/**
 * The full-bleed arrival: mansion atmosphere, the wordmark, three doorways and
 * the stock counter in the corner. Copy and buttons come from the homepage
 * hero record; the number itself stays the live founding-edition inventory.
 */
export function HomeHero({
    edition,
    hero,
}: {
    edition: Edition;
    hero: HomeHeroContent;
}) {
    const { openNewsletter } = useShellActions();

    return (
        <section className="relative flex min-h-[calc(100vh-var(--topbar-h)-var(--nav-h))] items-end overflow-hidden bg-choc">
            {/*
             * The photograph must sit above the section's solid fill — a negative
             * z-index drops it behind `bg-choc` and the mansion never appears.
             */}
            {hero.imageUrl ? (
                <img
                    src={hero.imageUrl}
                    alt=""
                    className="absolute inset-0 h-full w-full object-cover"
                />
            ) : (
                <PlaceholderImage
                    asset="hero-mansion"
                    ratio={null}
                    alt=""
                    captioned={false}
                    loading="eager"
                    fetchPriority="high"
                    className="absolute inset-0 h-full w-full"
                />
            )}

            <div
                aria-hidden="true"
                className="absolute inset-0"
                style={{
                    backgroundImage: [
                        'linear-gradient(158deg, rgba(41,28,24,0.35) 0%, rgba(41,28,24,0.15) 35%, rgba(41,28,24,0.55) 70%, rgba(10,7,4,0.88) 100%)',
                        'radial-gradient(ellipse at 68% 28%, rgba(100,68,40,0.22) 0%, transparent 42%)',
                        'radial-gradient(ellipse at 20% 70%, rgba(35,22,12,0.4) 0%, transparent 50%)',
                    ].join(', '),
                }}
            />

            <div
                aria-hidden="true"
                className="absolute inset-0 bg-linear-to-b from-choc/25 via-transparent via-45% to-choc/85"
            />

            <div className="relative z-2 mx-auto w-full max-w-320 px-8 pb-16 md:px-20 md:pb-20">
                <p className="mb-5 flex items-center gap-3.5 font-sans text-[9px] font-light tracking-[0.4em] text-gold uppercase before:inline-block before:h-px before:w-6 before:bg-gold before:content-['']">
                    {hero.eyebrow}
                </p>

                <h1 className="font-serif text-[clamp(52px,7vw,92px)] leading-none font-normal tracking-[0.1em] text-cream uppercase">
                    {hero.title}
                    <span className="mt-1 block text-[clamp(30px,4vw,56px)] font-light tracking-[0.2em] text-cream/60">
                        {hero.titleAccent}
                    </span>
                </h1>

                <div className="my-6 h-px w-12 bg-gold" />

                <p className="mb-10 font-sans text-[10px] font-light tracking-[0.35em] text-gold uppercase">
                    {hero.tagline}
                </p>

                <div className="flex flex-wrap items-center gap-5">
                    {hero.buttons.map((button, index) => {
                        const variant = index === 0 ? 'hero' : 'ghost';

                        if (button.kind === 'newsletter') {
                            return (
                                <MaisonButton
                                    key={`${button.kind}-${index}`}
                                    variant={variant}
                                    onClick={openNewsletter}
                                >
                                    {button.label}
                                </MaisonButton>
                            );
                        }

                        if (!button.href) {
                            return null;
                        }

                        return (
                            <MaisonButton
                                key={button.href}
                                as={MaisonLink}
                                variant={variant}
                                href={button.href}
                            >
                                {button.label}
                            </MaisonButton>
                        );
                    })}
                </div>
            </div>

            {hero.showCounter ? (
                <div className="absolute right-8 bottom-16 z-2 hidden text-right max-[480px]:hidden sm:block md:right-20 md:bottom-20">
                    <div className="font-serif text-[72px] leading-none font-light text-gold lining-nums">
                        {String(edition.available).padStart(2, '0')}
                    </div>
                    <div className="my-2 ml-auto h-px w-8 bg-gold/30" />
                    <div className="font-sans text-[8px] font-light tracking-[0.3em] text-sand uppercase">
                        {hero.counterLines[0]}
                        <br />
                        {hero.counterLines[1]}
                    </div>
                </div>
            ) : null}
        </section>
    );
}
