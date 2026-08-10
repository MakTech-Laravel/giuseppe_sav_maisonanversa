import type { ComponentProps, ElementType } from 'react';
import { cn } from '@/lib/utils';

type TextLinkProps<T extends ElementType> = {
    as?: T;
    /** Gold underline, for a chocolate ground. */
    tone?: 'dark' | 'light';
} & Omit<ComponentProps<T>, 'as'>;

/**
 * The underlined text affordance used beside body copy. Kept separate from
 * `MaisonButton` because it is a sentence-level link rather than a call to
 * action — different size, different hover, different job.
 */
export function TextLink<T extends ElementType = 'a'>({
    as,
    tone = 'dark',
    className,
    ...props
}: TextLinkProps<T>) {
    const Component = (as ?? 'a') as ElementType;

    return (
        <Component
            className={cn(
                'mt-3 inline-flex items-center gap-2.5 border-b pb-0.75 font-sans text-[9px] tracking-[0.25em] uppercase transition-colors',
                tone === 'dark'
                    ? 'border-choc3 text-choc3 hover:border-gold2 hover:text-gold2'
                    : 'border-gold/40 text-gold hover:border-gold',
                className,
            )}
            {...props}
        />
    );
}
