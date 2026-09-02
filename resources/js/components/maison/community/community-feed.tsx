import { InfiniteScroll, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { flushSync } from 'react-dom';
import type { FeedPostData } from '@/components/maison/community/community-data';
import { FeedCompose } from '@/components/maison/community/feed-compose';
import { FeedPostCard } from '@/components/maison/community/feed-post-card';
import { Wrap } from '@/components/maison/ui/section';
import type { Paginated } from '@/types/admin';

type CommunityFeedProps = {
    posts: Paginated<FeedPostData>;
    onPostPublished: () => void;
};

const COMMUNITY_PAGE = 'maison/community';

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

export function CommunityFeed({ posts, onPostPublished }: CommunityFeedProps) {
    const page = usePage();
    const { auth, locale } = page.props;
    const userName = auth?.user?.name ?? 'Member';
    const userInitials = initialsFromName(userName);
    const hasScrollProp = page.scrollProps?.posts != null;
    const [scrollEnabled, setScrollEnabled] = useState(hasScrollProp);
    const [prevHasScrollProp, setPrevHasScrollProp] = useState(hasScrollProp);

    // InfiniteScroll reads the core page store (not React context) on mount.
    // That store updates before React swaps components, so tear the scroller
    // down synchronously on beforeUpdate when leaving the community page.
    useEffect(() => {
        return router.on('beforeUpdate', (event) => {
            if (event.detail.page.component !== COMMUNITY_PAGE) {
                flushSync(() => setScrollEnabled(false));
            }
        });
    }, []);

    // Re-enable the scroller once a scroll prop reappears. Adjusted during
    // render rather than in an effect — see
    // https://react.dev/learn/you-might-not-need-an-effect#adjusting-some-state-when-a-prop-changes
    if (hasScrollProp !== prevHasScrollProp) {
        setPrevHasScrollProp(hasScrollProp);

        if (hasScrollProp) {
            setScrollEnabled(true);
        }
    }

    const canInfiniteScroll = scrollEnabled && hasScrollProp;

    function handlePublish(text: string) {
        router.post(
            `/${locale}/community/posts`,
            { content: text },
            {
                preserveScroll: true,
                onSuccess: () => onPostPublished(),
            },
        );
    }

    const feed = posts.data.map((post) => (
        <FeedPostCard key={post.id} post={post} />
    ));

    return (
        <>
            <Wrap className="px-6 py-12 md:px-10 lg:px-20">
                <FeedCompose
                    initials={userInitials}
                    onPublish={handlePublish}
                />

                {canInfiniteScroll ? (
                    <InfiniteScroll data="posts" buffer={400}>
                        {feed}
                    </InfiniteScroll>
                ) : (
                    feed
                )}
            </Wrap>
        </>
    );
}
