import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Wrap } from '@/components/maison/ui/section';
import * as eventRoutes from '@/routes/community/events';
import * as sessionRoutes from '@/routes/community/sessions';
import { cn } from '@/lib/utils';

export type CommunityTab = 'feed' | 'courts';

const TABS: { id: CommunityTab; label: string }[] = [
    { id: 'feed', label: 'Feed' },
    { id: 'courts', label: 'Club Corners & Courts' },
];

type CommunityTabsProps = {
    activeTab: CommunityTab;
    onTabChange: (tab: CommunityTab) => void;
};

const tabClassName =
    'relative shrink-0 cursor-pointer bg-transparent px-5 py-4 font-sans text-[10px] tracking-[0.22em] uppercase transition-colors md:px-7';

const activeClassName =
    'font-medium text-choc after:absolute after:right-5 after:bottom-0 after:left-5 after:h-0.5 after:bg-choc md:after:right-7 md:after:left-7';

/**
 * Sticky community sub-nav. Sessions and events are full pages of their own,
 * so they sit here as links rather than in-page tabs.
 */
export function CommunityTabs({ activeTab, onTabChange }: CommunityTabsProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    const links = [
        { href: sessionRoutes.index.url(locale), label: 'Sessies' },
        { href: eventRoutes.index.url(locale), label: 'Exclusieve Events' },
    ];

    return (
        <div className="sticky top-[calc(var(--topbar-h)+var(--nav-h))] z-50 border-b border-gold/20 bg-cream">
            <Wrap className="px-6 md:px-10 lg:px-20">
                <div
                    role="tablist"
                    aria-label={t('Community')}
                    className="flex gap-1 overflow-x-auto overflow-y-hidden [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    {TABS.map((tab) => {
                        const isActive = activeTab === tab.id;

                        return (
                            <button
                                key={tab.id}
                                type="button"
                                role="tab"
                                aria-selected={isActive}
                                onClick={() => onTabChange(tab.id)}
                                className={cn(
                                    tabClassName,
                                    isActive
                                        ? activeClassName
                                        : 'text-stone hover:text-choc',
                                )}
                            >
                                {t(tab.label)}
                            </button>
                        );
                    })}

                    {links.map((link) => (
                        <Link
                            key={link.label}
                            href={link.href}
                            className={cn(
                                tabClassName,
                                'text-stone hover:text-choc',
                            )}
                        >
                            {t(link.label)}
                        </Link>
                    ))}
                </div>
            </Wrap>
        </div>
    );
}
