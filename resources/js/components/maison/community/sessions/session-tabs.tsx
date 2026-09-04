import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';

export type TabDefinition<T extends string> = {
    id: T;
    /** Dutch source copy, used directly as the i18n key. */
    label: string;
    count?: number;
};

type SessionTabsProps<T extends string> = {
    tabs: readonly TabDefinition<T>[];
    activeTab: T;
    /** Builds the href for a tab; tabs are real links so they are shareable. */
    hrefFor: (tab: T) => string;
    ariaLabel: string;
};

/**
 * Open / My / Past. Rendered as links rather than buttons so a tab can be
 * bookmarked and the browser back button walks between them.
 *
 * Stacked vertically below md so labels stay readable; horizontal on md+.
 */
export function SessionTabs<T extends string>({
    tabs,
    activeTab,
    hrefFor,
    ariaLabel,
}: SessionTabsProps<T>) {
    const { t } = useTranslation();

    return (
        <div
            role="tablist"
            aria-label={ariaLabel}
            className="flex flex-col border-b border-gold/20 md:[scrollbar-width:none] md:flex-row md:gap-1 md:overflow-x-auto md:overflow-y-hidden md:[-ms-overflow-style:none] md:[&::-webkit-scrollbar]:hidden"
        >
            {tabs.map((tab) => {
                const isActive = tab.id === activeTab;

                return (
                    <Link
                        key={tab.id}
                        href={hrefFor(tab.id)}
                        role="tab"
                        aria-selected={isActive}
                        preserveScroll
                        className={cn(
                            'relative w-full shrink-0 px-5 py-3.5 font-sans text-[10px] tracking-[0.22em] uppercase transition-colors md:w-auto md:px-7 md:py-4',
                            isActive
                                ? 'font-medium text-choc after:absolute after:right-5 after:bottom-0 after:left-5 after:h-0.5 after:bg-choc md:after:right-7 md:after:left-7'
                                : 'text-stone hover:text-choc',
                        )}
                    >
                        {t(tab.label)}
                        {tab.count != null && tab.count > 0 && (
                            <span className="ml-2 font-sans text-[10px] text-gold2">
                                {tab.count}
                            </span>
                        )}
                    </Link>
                );
            })}
        </div>
    );
}
