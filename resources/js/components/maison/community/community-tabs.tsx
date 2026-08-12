import { useTranslation } from 'react-i18next';
import { Wrap } from '@/components/maison/ui/section';
import { cn } from '@/lib/utils';

export type CommunityTab = 'feed' | 'courts' | 'sessions' | 'events';

const TABS: { id: CommunityTab; label: string }[] = [
    { id: 'feed', label: 'Feed' },
    { id: 'courts', label: 'Club Corners & Courts' },
    { id: 'sessions', label: 'Sessies Plannen' },
    { id: 'events', label: 'Exclusieve Events' },
];

type CommunityTabsProps = {
    activeTab: CommunityTab;
    onTabChange: (tab: CommunityTab) => void;
};

/**
 * Sticky community sub-nav. Active state uses an inset underline so it stays
 * visible even when the row clips overflow to hide scrollbars.
 */
export function CommunityTabs({ activeTab, onTabChange }: CommunityTabsProps) {
    const { t } = useTranslation();

    return (
        <div className="sticky top-[calc(var(--topbar-h)+var(--nav-h))] z-50 border-b border-gold/20 bg-cream">
            <Wrap className="px-6 md:px-10 lg:px-20">
                <div
                    role="tablist"
                    aria-label={t('Community')}
                    className="flex gap-1 overflow-x-auto overflow-y-hidden [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden"
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
                                    'relative shrink-0 cursor-pointer bg-transparent px-5 py-4 font-sans text-[10px] tracking-[0.22em] uppercase transition-colors md:px-7',
                                    isActive
                                        ? 'font-medium text-choc after:absolute after:right-5 after:bottom-0 after:left-5 after:h-0.5 after:bg-choc md:after:right-7 md:after:left-7'
                                        : 'text-stone hover:text-choc',
                                )}
                            >
                                {t(tab.label)}
                            </button>
                        );
                    })}
                </div>
            </Wrap>
        </div>
    );
}
