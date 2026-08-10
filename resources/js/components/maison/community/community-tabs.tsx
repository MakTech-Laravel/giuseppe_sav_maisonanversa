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

export function CommunityTabs({ activeTab, onTabChange }: CommunityTabsProps) {
    const { t } = useTranslation();

    return (
        <div className="sticky top-[130px] z-50 border-b-2 border-gold/15 bg-cream">
            <Wrap className="px-6 md:px-10 lg:px-20">
                <div className="flex overflow-x-auto">
                    {TABS.map((tab) => (
                        <button
                            key={tab.id}
                            type="button"
                            onClick={() => onTabChange(tab.id)}
                            className={cn(
                                '-mb-0.5 cursor-pointer border-b-2 border-transparent bg-transparent px-7 py-4.5 font-sans text-[10px] tracking-[0.22em] text-stone uppercase transition-colors',
                                activeTab === tab.id
                                    ? 'border-gold2 text-gold2'
                                    : 'hover:text-choc',
                            )}
                        >
                            {t(tab.label)}
                        </button>
                    ))}
                </div>
            </Wrap>
        </div>
    );
}
