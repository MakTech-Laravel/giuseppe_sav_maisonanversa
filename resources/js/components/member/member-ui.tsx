import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export function MemberPageHeader({
    eyebrow,
    title,
    description,
}: {
    eyebrow?: string;
    title: string;
    description?: string;
}) {
    return (
        <header className="mb-10">
            {eyebrow && (
                <p className="mb-2 font-sans text-[9px] tracking-[0.28em] text-gold uppercase">
                    {eyebrow}
                </p>
            )}
            <h1 className="font-serif text-[clamp(28px,4vw,42px)] leading-[1.15] font-normal text-choc">
                {title}
            </h1>
            {description && (
                <p className="mt-3 max-w-xl text-[15px] leading-[1.8] text-choc3">
                    {description}
                </p>
            )}
            <span
                aria-hidden="true"
                className="mt-5 block h-px w-10 bg-gold"
            />
        </header>
    );
}

export function MemberPanel({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'border border-gold/20 bg-cream2 p-6 md:p-8',
                className,
            )}
        >
            {children}
        </div>
    );
}
