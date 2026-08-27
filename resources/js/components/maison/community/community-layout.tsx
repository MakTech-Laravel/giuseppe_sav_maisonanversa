import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { CommunityCourts } from '@/components/maison/community/community-courts';
import type {
    CommunityCourtPayload,
    FeedPostData,
} from '@/components/maison/community/community-data';
import { CommunityFeed } from '@/components/maison/community/community-feed';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import type { CommunityTab } from '@/components/maison/community/community-tabs';
import type { useCommunityToast } from '@/components/maison/community/community-toast';
import * as eventRoutes from '@/routes/community/events';
import type { Paginated } from '@/types/admin';

type CommunityLayoutProps = {
    toast: ReturnType<typeof useCommunityToast>;
    posts: Paginated<FeedPostData>;
    courts: CommunityCourtPayload[];
};

export function CommunityLayout({ toast, posts, courts }: CommunityLayoutProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
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
                    onViewEvents={() =>
                        router.visit(eventRoutes.index.url(locale))
                    }
                    onPostPublished={() =>
                        toast.show(t('Post geplaatst in de Community.'))
                    }
                />
            )}

            {activeTab === 'courts' && <CommunityCourts courts={courts} />}
        </div>
    );
}
