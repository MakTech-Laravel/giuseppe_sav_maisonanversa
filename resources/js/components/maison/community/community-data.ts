import type { CommunityTab } from '@/components/maison/community/community-tabs';

export type FeedComment = {
    id: string;
    name: string;
    initials: string;
    body: string;
    info: string;
};

export type FeedPostData = {
    id: string;
    /** User-authored posts render content as escaped React text, not via t(). */
    userAuthored?: boolean;
    official?: boolean;
    initials?: string;
    avatarBg?: string;
    name: string;
    info: string;
    badge: string;
    badgeOfficial?: boolean;
    content: string;
    excerpt?: string;
    is_truncated?: boolean;
    imageLabel?: string;
    likes: number;
    comments: FeedComment[];
    liked?: boolean;
};

export type CommunityCourtPayload = {
    id: string;
    title: string;
    body: string | null;
    location: string | null;
    lat: number | null;
    lng: number | null;
    pin_top: string | null;
    pin_left: string | null;
    coming: boolean;
};

export function tabFromIndex(index: number): CommunityTab {
    const tabs: CommunityTab[] = ['feed', 'courts'];

    return tabs[index] ?? 'feed';
}
