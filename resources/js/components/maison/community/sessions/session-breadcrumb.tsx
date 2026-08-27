import { Link } from '@inertiajs/react';
import { Fragment } from 'react';
import { useTranslation } from 'react-i18next';
import { Wrap } from '@/components/maison/ui/section';

export type Crumb = {
    /** Dutch source copy, used directly as the i18n key. */
    label: string;
    href?: string;
};

/**
 * COMMUNITY › SESSIES › SESSIE AANMAKEN — the trail above each session page.
 */
export function SessionBreadcrumb({ crumbs }: { crumbs: Crumb[] }) {
    const { t } = useTranslation();

    return (
        <div className="border-b border-gold/15 bg-cream">
            <Wrap className="px-6 py-4 md:px-10 lg:px-20">
                <nav aria-label={t('Kruimelpad')}>
                    <ol className="flex flex-wrap items-center gap-2 font-sans text-[10px] tracking-[0.18em] text-stone uppercase">
                        {crumbs.map((crumb, index) => (
                            <Fragment key={crumb.label}>
                                {index > 0 && (
                                    <li aria-hidden="true" className="text-gold/50">
                                        ›
                                    </li>
                                )}
                                <li>
                                    {crumb.href ? (
                                        <Link
                                            href={crumb.href}
                                            className="transition-colors hover:text-choc"
                                        >
                                            {t(crumb.label)}
                                        </Link>
                                    ) : (
                                        <span className="text-choc">
                                            {t(crumb.label)}
                                        </span>
                                    )}
                                </li>
                            </Fragment>
                        ))}
                    </ol>
                </nav>
            </Wrap>
        </div>
    );
}
