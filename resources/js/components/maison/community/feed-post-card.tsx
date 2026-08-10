import { useState } from 'react';
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
    const [liked, setLiked] = useState(false);
    const [likeCount, setLikeCount] = useState(post.likes);

    function toggleLike() {
        setLiked((current) => {
            const next = !current;
            setLikeCount((count) => count + (next ? 1 : -1));

            return next;
        });
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
                    <div className="font-serif text-lg font-medium text-choc">
                        {t(post.name)}
                    </div>
                    <div className="mt-0.5 font-sans text-[10px] tracking-[0.1em] text-stone">
                        {t(post.info)}
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

            <p className="mb-4 text-[17px] leading-[1.75] whitespace-pre-wrap text-choc">
                {post.userAuthored ? post.content : t(post.content)}
            </p>

            {post.imageLabel && (
                <div className="mb-4 flex aspect-video items-center justify-center bg-linear-to-br from-[#2A1A10] to-[#291c18]">
                    <span className="font-serif text-[28px] text-gold/10 italic">
                        {t(post.imageLabel)}
                    </span>
                </div>
            )}

            <footer className="flex items-center gap-6 border-t border-gold/10 pt-3.5">
                <button
                    type="button"
                    onClick={toggleLike}
                    className={cn(
                        'flex cursor-pointer items-center gap-1.5 border-none bg-transparent font-sans text-[10px] tracking-[0.12em] text-stone transition-colors',
                        liked && 'text-gold2',
                    )}
                >
                    <span>{liked ? '♥' : '♡'}</span>
                    <span>
                        {likeCount} {t('likes')}
                    </span>
                </button>
                <button
                    type="button"
                    className="flex cursor-pointer items-center gap-1.5 border-none bg-transparent font-sans text-[10px] tracking-[0.12em] text-stone"
                >
                    <span>💬</span>
                    {post.comments} {t('reacties')}
                </button>
                <button
                    type="button"
                    className="ml-auto flex cursor-pointer items-center gap-1.5 border-none bg-transparent font-sans text-[10px] tracking-[0.12em] text-stone"
                >
                    <span>↗</span> {t('Delen')}
                </button>
            </footer>
        </article>
    );
}
