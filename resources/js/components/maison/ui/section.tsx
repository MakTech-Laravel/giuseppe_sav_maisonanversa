import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * The four background tones the pages alternate between. Each one carries its
 * own text colour, so a section never has to restate it and a nested element
 * can inherit something legible.
 */
const TONES = {
    cream: 'bg-cream text-choc',
    cream2: 'bg-cream2 text-choc',
    dark: 'bg-choc text-cream',
    choc2: 'bg-choc2 text-cream',
} as const;

export type SectionTone = keyof typeof TONES;

type SectionProps = ComponentProps<'section'> & {
    tone?: SectionTone;
    /** 96px of breathing room above and below, which most sections want. */
    padded?: boolean;
};

export function Section({
    tone = 'cream',
    padded = true,
    className,
    ...props
}: SectionProps) {
    return (
        <section
            className={cn(TONES[tone], padded && 'py-24', className)}
            {...props}
        />
    );
}

/**
 * The 1280px measure every section's content sits inside. Kept separate from
 * `Section` so a section can put a full-bleed element beside a wrapped one.
 */
export function Wrap({ className, ...props }: ComponentProps<'div'>) {
    return (
        <div
            className={cn('mx-auto w-full max-w-320 px-8 md:px-20', className)}
            {...props}
        />
    );
}
