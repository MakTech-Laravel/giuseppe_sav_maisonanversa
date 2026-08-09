import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type EyebrowProps = ComponentProps<'span'> & {
    /**
     * The darker gold, for use on a cream ground where the lighter one is too
     * faint to read.
     */
    tone?: 'gold' | 'gold2';
};

/** The small tracked-out label that introduces almost every section. */
export function Eyebrow({ tone = 'gold', className, ...props }: EyebrowProps) {
    return (
        <span
            className={cn(
                'block font-sans text-[9px] font-light tracking-[0.35em] uppercase',
                tone === 'gold' ? 'text-gold' : 'text-gold2',
                className,
            )}
            {...props}
        />
    );
}
