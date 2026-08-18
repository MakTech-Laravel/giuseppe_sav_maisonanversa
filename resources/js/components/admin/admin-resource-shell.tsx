import { motion } from 'motion/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

interface AdminResourceShellProps {
    children: ReactNode;
    aside?: ReactNode;
    className?: string;
}

/**
 * Shared create / edit / show layout: primary column + sticky sidebar on lg+.
 */
export function AdminResourceShell({
    children,
    aside,
    className,
}: AdminResourceShellProps) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.25 }}
            className={cn(
                'grid w-full gap-6 lg:grid-cols-[minmax(0,1fr)_17.5rem] lg:items-start xl:grid-cols-[minmax(0,1fr)_20rem]',
                className,
            )}
        >
            <div className="min-w-0 space-y-6">{children}</div>
            {aside ? (
                <aside className="space-y-4 lg:sticky lg:top-6">{aside}</aside>
            ) : null}
        </motion.div>
    );
}

interface AdminPanelProps {
    title?: string;
    description?: string;
    children: ReactNode;
    className?: string;
    flush?: boolean;
}

export function AdminPanel({
    title,
    description,
    children,
    className,
    flush = false,
}: AdminPanelProps) {
    return (
        <section
            className={cn(
                'overflow-hidden rounded-xl border bg-card shadow-sm',
                className,
            )}
        >
            {(title || description) && (
                <header className="border-b bg-muted/30 px-5 py-4 sm:px-6">
                    {title ? (
                        <h2 className="text-sm font-semibold tracking-tight">
                            {title}
                        </h2>
                    ) : null}
                    {description ? (
                        <p className="mt-1 text-xs text-muted-foreground sm:text-sm">
                            {description}
                        </p>
                    ) : null}
                </header>
            )}
            <div className={flush ? undefined : 'p-5 sm:p-6'}>{children}</div>
        </section>
    );
}
