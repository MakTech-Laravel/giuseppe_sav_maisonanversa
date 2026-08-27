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
            className="flex gap-1 overflow-x-auto overflow-y-hidden border-b border-gold/20 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
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
                            'relative shrink-0 px-5 py-4 font-sans text-[10px] tracking-[0.22em] uppercase transition-colors md:px-7',
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
