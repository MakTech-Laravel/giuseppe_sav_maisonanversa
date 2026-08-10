import { useState } from 'react';
import {
    INITIAL_FEED_POSTS,
    type FeedPostData,
} from '@/components/maison/community/community-data';
import { FeedCompose } from '@/components/maison/community/feed-compose';
import { FeedPostCard } from '@/components/maison/community/feed-post-card';
import { FeedSidebar } from '@/components/maison/community/feed-sidebar';
import { Wrap } from '@/components/maison/ui/section';

type CommunityFeedProps = {
    onViewEvents: () => void;
    onPostPublished: () => void;
};

export function CommunityFeed({
    onViewEvents,
    onPostPublished,
}: CommunityFeedProps) {
    const [posts, setPosts] = useState<FeedPostData[]>(INITIAL_FEED_POSTS);

    function handlePublish(text: string) {
        const newPost: FeedPostData = {
            id: `user-${Date.now()}`,
            userAuthored: true,
            initials: 'YS',
            avatarBg: '#291c18',
            name: 'Yusuf Savran',
            info: 'Nr. 001 · Zojuist',
            badge: 'FC Lid',
            content: text,
            likes: 0,
            comments: 0,
        };

        setPosts((current) => [newPost, ...current]);
        onPostPublished();
    }

    return (
        <Wrap className="grid items-start gap-12 px-6 py-12 md:px-10 lg:grid-cols-[1fr_360px] lg:px-20">
            <div>
                <FeedCompose onPublish={handlePublish} />

                {posts.map((post) => (
                    <FeedPostCard key={post.id} post={post} />
                ))}
            </div>

            <FeedSidebar onViewEvents={onViewEvents} />
        </Wrap>
    );
}
