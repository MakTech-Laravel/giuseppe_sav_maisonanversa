import { InfiniteScroll, router, usePage } from '@inertiajs/react';
import { CommunityCirclePanel } from '@/components/maison/community/community-circle-panel';
import type { FeedPostData } from '@/components/maison/community/community-data';
import { FeedCompose } from '@/components/maison/community/feed-compose';
import { FeedPostCard } from '@/components/maison/community/feed-post-card';
import { Wrap } from '@/components/maison/ui/section';
import type { Paginated } from '@/types/admin';

type CommunityFeedProps = {
    posts: Paginated<FeedPostData>;
    onViewEvents: () => void;
    onPostPublished: () => void;
};

function initialsFromName(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return 'MA';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
}

export function CommunityFeed({
    posts,
    onViewEvents,
    onPostPublished,
}: CommunityFeedProps) {
    const { auth, locale } = usePage().props;
    const userName = auth.user?.name ?? 'Member';
    const userInitials = initialsFromName(userName);

    function handlePublish(text: string) {
        router.post(`/${locale}/community/posts`, { content: text }, {
            preserveScroll: true,
            onSuccess: () => onPostPublished(),
        });
    }

    return (
        <>
            <Wrap className="mx-auto max-w-3xl px-6 py-12 md:px-10 lg:px-20">
                <FeedCompose
                    initials={userInitials}
                    onPublish={handlePublish}
                />

                <InfiniteScroll data="posts" buffer={400}>
                    {posts.data.map((post) => (
                        <FeedPostCard key={post.id} post={post} />
                    ))}
                </InfiniteScroll>
            </Wrap>

            <CommunityCirclePanel onViewEvents={onViewEvents} />
        </>
    );
}
