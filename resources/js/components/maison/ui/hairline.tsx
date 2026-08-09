import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type HairlineProps = Omit<ComponentProps<'span'>, 'children'> & {
    /** A short 32px rule rather than one that spans its container. */
    short?: boolean;
    /** For a chocolate ground, where the gold needs more presence. */
    tone?: 'light' | 'dark';
};

/** The 1px gold divider that separates rows and captions. */
export function Hairline({
    short = false,
    tone = 'light',
    className,
    ...props
}: HairlineProps) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'block h-px',
                short ? 'w-8' : 'w-full',
                tone === 'light' ? 'bg-gold/30' : 'bg-gold/20',
                className,
            )}
            {...props}
        />
    );
}
