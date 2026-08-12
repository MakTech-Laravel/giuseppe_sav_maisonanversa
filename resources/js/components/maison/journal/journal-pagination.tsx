import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { cn } from '@/lib/utils';
import type { JournalPaginator } from '@/types/journal';

export function JournalPagination({
    articles,
}: {
    articles: JournalPaginator;
}) {
    const { t } = useTranslation();

    if (articles.last_page <= 1) {
        return null;
    }

    const pages = Array.from({ length: articles.last_page }, (_, index) => index + 1);

    return (
        <nav
            aria-label={t('Journal paginering')}
            className="mt-16 flex flex-col items-center"
        >
            <GoldRule center className="mx-auto" />

            <div className="mt-8 flex flex-wrap items-center justify-center gap-2">
                <PageControl
                    href={articles.prev_page_url}
                    label={t('Vorige pagina')}
                />

                {pages.map((page) => {
                    const link = articles.links.find(
                        (item) => item.label === String(page),
                    );
                    const active = page === articles.current_page;

                    return (
                        <PageNumber
                            key={page}
                            href={active ? null : (link?.url ?? null)}
                            active={active}
                            label={String(page)}
                        />
                    );
                })}

                <PageControl
                    href={articles.next_page_url}
                    label={t('Volgende pagina')}
                />
            </div>
        </nav>
    );
}

function PageControl({ href, label }: { href: string | null; label: string }) {
    const classes =
        'inline-flex min-h-11 items-center px-4 font-sans text-[9px] tracking-[0.22em] text-choc uppercase';

    if (!href) {
        return (
            <span className={cn(classes, 'pointer-events-none text-choc/30')}>
                {label}
            </span>
        );
    }

    return (
        <Link
            href={href}
            preserveScroll
            className={cn(classes, 'transition-colors hover:text-gold')}
        >
            {label}
        </Link>
    );
}

function PageNumber({
    href,
    active,
    label,
}: {
    href: string | null;
    active: boolean;
    label: string;
}) {
    const classes =
        'inline-flex min-h-11 min-w-11 items-center justify-center font-serif text-[18px]';

    if (active || !href) {
        return (
            <span
                aria-current={active ? 'page' : undefined}
                className={cn(classes, 'text-gold')}
            >
                {label}
            </span>
        );
    }

    return (
        <Link
            href={href}
            preserveScroll
            className={cn(classes, 'text-choc3 transition-colors hover:text-gold')}
        >
            {label}
        </Link>
    );
}
