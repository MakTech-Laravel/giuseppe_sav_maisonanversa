import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export const memberFieldClassName =
    'h-11 rounded-none border border-gold/45 bg-choc/60 text-cream shadow-none placeholder:text-stone/80 focus-visible:border-gold focus-visible:ring-1 focus-visible:ring-gold/40';

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
            <h1 className="font-serif text-[clamp(28px,4vw,42px)] leading-[1.15] font-normal text-cream">
                {title}
            </h1>
            {description && (
                <p className="mt-3 max-w-xl text-[15px] leading-[1.8] text-sand">
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
                'border border-gold/25 bg-choc3 p-6 text-cream md:p-8',
                className,
            )}
        >
            {children}
        </div>
    );
}

export function MemberSectionTitle({
    title,
    description,
}: {
    title: string;
    description?: string;
}) {
    return (
        <div className="mb-6 border-b border-gold/20 pb-4">
            <h2 className="font-serif text-[22px] text-cream">{title}</h2>
            {description && (
                <p className="mt-1.5 text-[13px] leading-relaxed text-sand">
                    {description}
                </p>
            )}
        </div>
    );
}

export function MemberStatusPill({
    children,
    tone = 'neutral',
}: {
    children: ReactNode;
    tone?: 'neutral' | 'success' | 'warn';
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center border px-2.5 py-1 font-sans text-[9px] tracking-[0.18em] uppercase',
                tone === 'success' &&
                    'border-gold/50 bg-gold/15 text-gold',
                tone === 'warn' &&
                    'border-stone/40 bg-choc text-sand',
                tone === 'neutral' &&
                    'border-gold/30 bg-choc/50 text-sand',
            )}
        >
            {children}
        </span>
    );
}
