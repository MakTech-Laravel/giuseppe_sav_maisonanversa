import type { ReactNode } from 'react';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Wrap } from '@/components/maison/ui/section';
import { cn } from '@/lib/utils';

type PageHeroProps = {
    /** Small tracked label above the gold rule. */
    eyebrow?: ReactNode;
    /** Rendered as the `h1`. Pass a fragment to italicise part of it in gold. */
    title: ReactNode;
    subtitle?: ReactNode;
    children?: ReactNode;
    className?: string;
};

/**
 * The masthead every inner page opens with: chocolate ground, a faint 48px
 * drafting grid and a glow bleeding down from the top edge.
 */
export function PageHero({
    eyebrow,
    title,
    subtitle,
    children,
    className,
}: PageHeroProps) {
    return (
        <section
            className={cn(
                'relative overflow-hidden border-b border-gold/10 bg-choc2 pt-18 pb-16 text-center text-cream',
                className,
            )}
        >
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(141,112,90,0.025)_1px,transparent_1px),linear-gradient(90deg,rgba(141,112,90,0.025)_1px,transparent_1px)] bg-[size:48px_48px]"
            />

            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_50%_0%,rgba(141,112,90,0.04)_0%,transparent_60%)]"
            />

            <Wrap className="relative">
                {eyebrow && (
                    <Eyebrow className="mb-0 text-center">{eyebrow}</Eyebrow>
                )}

                {eyebrow && <GoldRule center className="mx-auto" />}

                <h1 className="font-serif text-[clamp(36px,5vw,68px)] leading-[1.1] font-normal [&_em]:text-gold [&_em]:italic">
                    {title}
                </h1>

                {subtitle && (
                    <p className="mx-auto mt-3 max-w-135 font-sans text-[17px] leading-[1.8] text-sand">
                        {subtitle}
                    </p>
                )}

                {children}
            </Wrap>
        </section>
    );
}
