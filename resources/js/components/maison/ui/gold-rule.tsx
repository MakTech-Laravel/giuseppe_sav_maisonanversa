import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type GoldRuleProps = Omit<ComponentProps<'span'>, 'children'> & {
    center?: boolean;
};

/**
 * The 40px gold hairline under a heading.
 *
 * The slow pulse lives in `app.css` as `.gold-rule`, where the reduced-motion
 * query can switch it off; only the geometry is expressed in utilities.
 */
export function GoldRule({
    center = false,
    className,
    ...props
}: GoldRuleProps) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'gold-rule my-4 block h-px w-10 bg-gold',
                center && 'mx-auto',
                className,
            )}
            {...props}
        />
    );
}
