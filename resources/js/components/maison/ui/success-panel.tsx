import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

type SuccessPanelProps = {
    title: ReactNode;
    children?: ReactNode;
    /** The mark above the heading. A diamond, in keeping with the house. */
    icon?: ReactNode;
    className?: string;
};

/**
 * What a form shows once it has been sent.
 *
 * Announced politely so a screen reader hears the confirmation without being
 * interrupted; the prototype swapped a `display: none` class and said nothing,
 * leaving a non-sighted visitor unsure whether the form had submitted.
 */
export function SuccessPanel({
    title,
    children,
    icon = '◆',
    className,
}: SuccessPanelProps) {
    return (
        <div
            role="status"
            aria-live="polite"
            className={cn('py-5 text-center', className)}
        >
            <div aria-hidden="true" className="mb-3 text-[32px] text-gold">
                {icon}
            </div>

            <h3 className="font-serif text-2xl font-medium text-choc">
                {title}
            </h3>

            {children && (
                <div className="mt-2 font-sans text-[15px] text-choc3">
                    {children}
                </div>
            )}
        </div>
    );
}
