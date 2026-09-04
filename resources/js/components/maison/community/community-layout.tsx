import { useTranslation } from 'react-i18next';
import type { FeedPostData } from '@/components/maison/community/community-data';
import { CommunityFeed } from '@/components/maison/community/community-feed';
import { CommunityTabs } from '@/components/maison/community/community-tabs';
import type { useCommunityToast } from '@/components/maison/community/community-toast';
import type { Paginated } from '@/types/admin';

type CommunityLayoutProps = {
    toast: ReturnType<typeof useCommunityToast>;
    posts: Paginated<FeedPostData>;
};

export function CommunityLayout({ toast, posts }: CommunityLayoutProps) {
    const { t } = useTranslation();

    return (
        <div className="min-h-150 bg-cream">
            <CommunityTabs />

            <CommunityFeed
                posts={posts}
                onPostPublished={() =>
                    toast.show(t('Post geplaatst in de Community.'))
                }
            />
        </div>
    );
}
