import { cva } from 'class-variance-authority';
import type { VariantProps } from 'class-variance-authority';
import type { ComponentProps, ElementType } from 'react';
import { cn } from '@/lib/utils';

/**
 * The house's button vocabulary.
 *
 * The prototype grew nine near-identical classes — `btn-hero`, `btn-choc`,
 * `btn-gold`, `btn-outline-cream`, `btn-full-gold`, `btn-full-outline`,
 * `btn-submit`, `btn-back` and `btn-hero-ghost` — differing mostly in whether
 * they sat on a dark or a light ground and whether they filled their column.
 * Those two questions are the `variant` and `block` axes here.
 */
const maisonButtonVariants = cva(
    'inline-flex items-center justify-center gap-3 font-sans uppercase transition-all disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                /** Outlined in cream, for a photographic or chocolate ground. */
                hero: 'border border-cream/40 px-7 py-3.5 text-[9px] tracking-[0.25em] text-cream hover:border-gold hover:bg-gold hover:text-choc hover:shadow-[0_4px_20px_rgba(141,112,90,0.2)]',
                /** Outlined in chocolate, for a cream ground. */
                choc: 'border border-choc px-6 py-3.25 text-[9px] tracking-[0.25em] text-choc hover:bg-choc hover:text-cream',
                /** The filled gold call to action. */
                gold: 'bg-gold px-6 py-4 text-[10px] font-medium tracking-[0.25em] text-choc hover:bg-gold2 hover:shadow-[0_4px_24px_rgba(141,112,90,0.25)]',
                /** Filled chocolate that turns gold, for a cream ground. */
                filled: 'border border-choc bg-choc px-6 py-4.5 text-[10px] font-medium tracking-[0.25em] text-cream hover:border-gold hover:bg-gold hover:text-choc',
                /** A quiet outline on a dark ground. */
                outlineCream:
                    'border border-cream/20 px-6 py-3.5 text-[9px] font-light tracking-[0.2em] text-cream/60 hover:border-cream/50 hover:text-cream',
                /** A quiet outline on a cream ground. */
                outlineChoc:
                    'border border-choc/30 px-6 py-3.75 text-[9px] font-light tracking-[0.2em] text-choc3 hover:border-choc hover:text-choc',
                /** Underlined text, the lightest affordance of the set. */
                ghost: 'border-b border-cream/20 pb-1 text-[9px] font-light tracking-[0.25em] text-cream/60 hover:border-gold hover:text-gold',
            },
            /** Fills its column, for a form or a narrow card. */
            block: {
                true: 'w-full',
                false: '',
            },
        },
        defaultVariants: {
            variant: 'hero',
            block: false,
        },
    },
);

type MaisonButtonProps<T extends ElementType> = {
    /** Render as an anchor or a `MaisonLink` when the button navigates. */
    as?: T;
} & VariantProps<typeof maisonButtonVariants> &
    Omit<ComponentProps<T>, 'as'>;

export function MaisonButton<T extends ElementType = 'button'>({
    as,
    variant,
    block,
    className,
    ...props
}: MaisonButtonProps<T>) {
    const Component = as ?? 'button';

    return (
        <Component
            data-magnetic
            className={cn(maisonButtonVariants({ variant, block }), className)}
            {...(Component === 'button' ? { type: 'button' } : {})}
            {...props}
        />
    );
}

export { maisonButtonVariants };
