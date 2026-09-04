import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import type { FeedPostData } from '@/components/maison/community/community-data';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Monogram } from '@/components/maison/ui/monogram';
import { cn } from '@/lib/utils';

type FeedPostCardProps = {
    post: FeedPostData;
};

export function FeedPostCard({ post }: FeedPostCardProps) {
    const { t } = useTranslation();
    const { auth, locale } = usePage().props;
    const [commentsOpen, setCommentsOpen] = useState(false);
    const [expanded, setExpanded] = useState(false);
    const [draft, setDraft] = useState('');

    const displayContent = post.userAuthored ? post.content : t(post.content);
    const showTruncated = (post.is_truncated ?? false) && !expanded;
    const visibleContent = showTruncated
        ? (post.excerpt ?? displayContent)
        : displayContent;

    function toggleLike() {
        router.post(
            `/${locale}/community/posts/${post.id}/like`,
            {},
            { preserveScroll: true },
        );
    }

    function hideFromWall() {
        router.post(
            `/${locale}/community/posts/${post.id}/hide`,
            {},
            { preserveScroll: true },
        );
    }

    function submitComment(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const body = draft.trim();

        if (!body || !auth.user) {
            return;
        }

        router.post(
            `/${locale}/community/posts/${post.id}/comments`,
            { body },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDraft('');
                    setCommentsOpen(true);
                },
            },
        );
    }

    return (
        <article className="mb-4 border border-gold/12 bg-cream2 p-7 transition-colors hover:border-gold/25">
            <header className="mb-3.5 flex items-start gap-3.5">
                {post.official ? (
                    <Monogram
                        size="lg"
                        emphasis
                        className="border-gold bg-choc"
                    >
                        <PlaceholderImage
                            asset="logo-icon"
                            ratio="1 / 1"
                            alt="Maison Anversa"
                            captioned={false}
                            className="size-full rounded-full"
                        />
                    </Monogram>
                ) : (
                    <Monogram
                        initials={post.initials}
                        size="lg"
                        className="border-gold/30"
                        style={{ backgroundColor: post.avatarBg }}
                    />
                )}

                <div className="flex-1">
                    <div className="flex items-baseline gap-2">
                        <span className="font-serif text-lg font-medium text-choc">
                            {post.userAuthored ? post.name : t(post.name)}
                        </span>
                        {post.editionNumber && (
                            <span className="font-sans text-[9px] tracking-[0.15em] text-gold2 uppercase">
                                {t('Nr. {{number}}', {
                                    number: post.editionNumber,
                                })}
                            </span>
                        )}
                    </div>
                    <div className="mt-0.5 font-sans text-[10px] tracking-[0.1em] text-stone">
                        {post.userAuthored ? post.info : t(post.info)}
                    </div>
                </div>

                <div
                    className={cn(
                        'border px-2.5 py-0.5 font-sans text-[9px] tracking-[0.15em] uppercase',
                        post.badgeOfficial
                            ? 'border-gold/35 bg-gold/15 text-gold'
                            : 'border-gold/20 bg-gold/10 text-gold2',
                    )}
                >
                    {t(post.badge)}
                </div>
            </header>

            <div className="mb-4">
                <p className="text-[17px] leading-[1.75] whitespace-pre-wrap text-choc">
                    {visibleContent}
                    {showTruncated ? '…' : ''}
                </p>
                {post.is_truncated && (
                    <button
                        type="button"
                        onClick={() => setExpanded((value) => !value)}
                        className="mt-1 cursor-pointer border-none bg-transparent font-sans text-[10px] tracking-[0.12em] text-gold2 underline-offset-2 hover:underline"
                    >
                        {expanded ? t('Minder lezen') : t('Meer lezen')}
                    </button>
                )}
            </div>

            {post.imageLabel && (
                <div className="mb-4 flex aspect-video items-center justify-center bg-linear-to-br from-[#2A1A10] to-[#291c18]">
                    <span className="font-serif text-[28px] text-gold/10 italic">
                        {t(post.imageLabel)}
                    </span>
                </div>
            )}

            <footer className="flex flex-wrap items-center gap-6 border-t border-gold/10 pt-3.5">
                <button
                    type="button"
                    onClick={toggleLike}
                    className={cn(
                        'flex cursor-pointer items-center gap-1.5 border-none bg-transparent font-sans text-[10px] tracking-[0.12em] text-stone transition-colors',
                        post.liked && 'text-gold2',
                    )}
                >
                    <span>{post.liked ? '♥' : '♡'}</span>
                    <span>
                        {post.likes} {t('likes')}
                    </span>
                </button>
                <button
                    type="button"
                    onClick={() => setCommentsOpen((open) => !open)}
                    className={cn(
                        'flex cursor-pointer items-center gap-1.5 border-none bg-transparent font-sans text-[10px] tracking-[0.12em] text-stone transition-colors',
                        commentsOpen && 'text-gold2',
                    )}
                >
                    <span>💬</span>
                    {post.comments.length} {t('reacties')}
                </button>
                {auth.user && (
                    <button
                        type="button"
                        onClick={hideFromWall}
                        className="flex cursor-pointer items-center gap-1.5 border-none bg-transparent font-sans text-[10px] tracking-[0.12em] text-stone"
                    >
                        {t('Verbergen')}
                    </button>
                )}
            </footer>

            {commentsOpen && (
                <div className="mt-4 border-t border-gold/10 pt-4">
                    <ul className="mb-4 flex flex-col gap-3.5">
                        {post.comments.map((comment) => (
                            <li
                                key={comment.id}
                                className="flex items-start gap-3"
                            >
                                <Monogram
                                    initials={comment.initials}
                                    size="sm"
                                    className="border-gold/20"
                                />
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                        <span className="font-serif text-sm font-medium text-choc">
                                            {comment.name}
                                        </span>
                                        {comment.editionNumber && (
                                            <span className="font-sans text-[9px] tracking-[0.15em] text-gold2 uppercase">
                                                {t('Nr. {{number}}', {
                                                    number: comment.editionNumber,
                                                })}
                                            </span>
                                        )}
                                        <span className="font-sans text-[9px] tracking-[0.1em] text-stone">
                                            {comment.info}
                                        </span>
                                    </div>
                                    <p className="mt-1 text-[14px] leading-[1.6] text-choc3">
                                        {comment.body}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>

                    <form
                        onSubmit={submitComment}
                        className="flex flex-col gap-2.5 sm:flex-row sm:items-start"
                    >
                        <label
                            className="sr-only"
                            htmlFor={`comment-${post.id}`}
                        >
                            {t('Schrijf een reactie')}
                        </label>
                        <input
                            id={`comment-${post.id}`}
                            type="text"
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            placeholder={t('Plaats uw reactie')}
                            className="min-w-0 flex-1 border border-gold/20 bg-cream px-3.5 py-2.5 font-serif text-sm text-choc outline-none focus:border-gold2"
                        />
                        <button
                            type="submit"
                            className="shrink-0 bg-choc px-4 py-2.5 font-sans text-[10px] font-medium tracking-[0.18em] text-cream uppercase transition-colors hover:bg-gold2"
                        >
                            {t('Reageer')}
                        </button>
                    </form>
                </div>
            )}
        </article>
    );
}
