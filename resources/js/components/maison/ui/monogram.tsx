import { cva } from 'class-variance-authority';
import type { VariantProps } from 'class-variance-authority';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * The circular gold-ringed initials that stand in for a member's photograph,
 * at the three sizes the community pages use: 36px in the sidebar, 40px beside
 * a session and 44px on a post or the composer.
 */
const monogramVariants = cva(
    'flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-choc2 font-serif text-gold',
    {
        variants: {
            size: {
                sm: 'size-9 border-[1.5px] text-[13px]',
                md: 'size-10 border-2 text-[14px]',
                lg: 'size-11 border-[1.5px] text-base',
            },
            /** A full-strength ring, marking the member reading the page. */
            emphasis: {
                true: 'border-gold',
                false: 'border-gold/25',
            },
        },
        defaultVariants: {
            size: 'lg',
            emphasis: false,
        },
    },
);

type MonogramProps = ComponentProps<'div'> &
    VariantProps<typeof monogramVariants> & {
        /** Initials. Ignored when children are supplied, e.g. a house mark. */
        initials?: string;
    };

export function Monogram({
    initials,
    size,
    emphasis,
    className,
    children,
    ...props
}: MonogramProps) {
    return (
        <div
            className={cn(monogramVariants({ size, emphasis }), className)}
            {...props}
        >
            {children ?? initials}
        </div>
    );
}

export { monogramVariants };
