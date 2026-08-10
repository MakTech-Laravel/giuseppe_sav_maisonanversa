import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { CommunityCourts } from '@/components/maison/community/community-courts';
import { CommunityEvents } from '@/components/maison/community/community-events';
import { CommunityFeed } from '@/components/maison/community/community-feed';
import { CommunitySessions } from '@/components/maison/community/community-sessions';
import {
    CommunityTabs,
    type CommunityTab,
} from '@/components/maison/community/community-tabs';
import type { useCommunityToast } from '@/components/maison/community/community-toast';

type CommunityLayoutProps = {
    toast: ReturnType<typeof useCommunityToast>;
};

export function CommunityLayout({ toast }: CommunityLayoutProps) {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState<CommunityTab>('feed');

    return (
        <div className="min-h-150 bg-cream">
            <CommunityTabs activeTab={activeTab} onTabChange={setActiveTab} />

            {activeTab === 'feed' && (
                <CommunityFeed
                    onViewEvents={() => setActiveTab('events')}
                    onPostPublished={() =>
                        toast.show(t('Post geplaatst in de Community.'))
                    }
                />
            )}

            {activeTab === 'courts' && <CommunityCourts />}

            {activeTab === 'sessions' && (
                <CommunitySessions
                    onJoin={() =>
                        toast.show(t('U bent aangemeld voor de sessie.'))
                    }
                />
            )}

            {activeTab === 'events' && (
                <CommunityEvents
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
