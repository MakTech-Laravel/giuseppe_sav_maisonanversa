import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { CommunityCourts } from '@/components/maison/community/community-courts';
import type {
    CommunityCourtPayload,
    CommunityEventPayload,
    CommunitySessionPayload,
    FeedPostData,
} from '@/components/maison/community/community-data';
import { CommunityEvents } from '@/components/maison/community/community-events';
import { CommunityFeed } from '@/components/maison/community/community-feed';
import { CommunitySessions } from '@/components/maison/community/community-sessions';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import type { CommunityTab } from '@/components/maison/community/community-tabs';
import type { useCommunityToast } from '@/components/maison/community/community-toast';
import type { Paginated } from '@/types/admin';

type CommunityLayoutProps = {
    toast: ReturnType<typeof useCommunityToast>;
    posts: Paginated<FeedPostData>;
    sessions: CommunitySessionPayload[];
    events: CommunityEventPayload[];
    courts: CommunityCourtPayload[];
};

export function CommunityLayout({
    toast,
    posts,
    sessions,
    events,
    courts,
}: CommunityLayoutProps) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState<CommunityTab>('feed');

    function handleTabChange(tab: CommunityTab): void {
        setActiveTab(tab);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    return (
        <div className="min-h-150 bg-cream">
            <CommunityTabs activeTab={activeTab} onTabChange={handleTabChange} />

            {activeTab === 'feed' && (
                <CommunityFeed
                    posts={posts}
                    onViewEvents={() => handleTabChange('events')}
                    onPostPublished={() =>
                        toast.show(t('Post geplaatst in de Community.'))
                    }
                />
            )}

            {activeTab === 'courts' && <CommunityCourts courts={courts} />}

            {activeTab === 'sessions' && (
                <CommunitySessions
                    sessions={sessions}
                    onJoin={() =>
                        toast.show(t('U bent aangemeld voor de sessie.'))
                    }
                />
            )}

            {activeTab === 'events' && (
                <CommunityEvents
                    events={events}
                    onRsvp={() =>
                        toast.show(
                            t(
                                'U bent aangemeld. U ontvangt een bevestiging per e-mail.',
                            ),
                        )
                    }
                />
            )}
        </div>
    );
}
