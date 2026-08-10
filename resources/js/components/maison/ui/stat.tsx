import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type StatProps = {
    value: ReactNode;
    label: ReactNode;
    /** The lighter gold, for a chocolate ground. */
    tone?: 'gold' | 'gold2';
    className?: string;
};

/**
 * A figure over its caption: serif numeral in gold above a tracked-out label.
 * Used for club courts, member counts and edition numbers.
 */
export function Stat({ value, label, tone = 'gold2', className }: StatProps) {
    return (
        <div className={cn('text-center', className)}>
            <div
                className={cn(
                    'font-serif text-[22px] leading-none font-light',
                    tone === 'gold' ? 'text-gold' : 'text-gold2',
                )}
            >
                {value}
            </div>

            <div className="mt-1.5 font-sans text-[8px] tracking-[0.15em] text-stone uppercase">
                {label}
            </div>
        </div>
    );
}
