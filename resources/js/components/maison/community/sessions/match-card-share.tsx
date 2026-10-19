import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';
import type { MatchCardFormat, MatchCardLinks } from '@/types/session';

type MatchCardShareProps = {
    card: MatchCardLinks;
    title: string;
    className?: string;
};

/**
 * Story / Post preview of the match card with share, download and copy-link.
 * Touch devices get the native share sheet with the PNG itself (so it can go
 * straight to an Instagram Story) and the public link as text; desktops
 * download the file.
 */
export function MatchCardShare({
    card,
    title,
    className,
}: MatchCardShareProps) {
    const { t } = useTranslation();
    const [format, setFormat] = useState<MatchCardFormat>('story');
    const [sharing, setSharing] = useState(false);
    const [copiedText, copy] = useClipboard();

    const imageUrl = format === 'story' ? card.story_url : card.post_url;
    const downloadUrl =
        format === 'story' ? card.download_story_url : card.download_post_url;

    async function handleShare() {
        const prefersDownload =
            typeof window.matchMedia === 'function' &&
            !window.matchMedia('(pointer: coarse)').matches;

        if (prefersDownload || typeof navigator.share !== 'function') {
            window.location.assign(downloadUrl);

            return;
        }

        setSharing(true);

        try {
            const response = await fetch(imageUrl);
            const blob = await response.blob();
            const file = new File([blob], `maison-anversa-${format}.png`, {
                type: 'image/png',
            });

            if (navigator.canShare?.({ files: [file] })) {
                await navigator.share({
                    files: [file],
                    title,
                    text: card.share_url,
                });
            } else {
                await navigator.share({ title, url: card.share_url });
            }
        } catch (error) {
            if (
                !(error instanceof DOMException && error.name === 'AbortError')
            ) {
                window.location.assign(downloadUrl);
            }
        } finally {
            setSharing(false);
        }
    }

    return (
        <div className={cn('flex flex-col gap-5', className)}>
            <div>
                <h2 className="mb-1 font-sans text-[11px] font-medium tracking-[0.22em] text-choc uppercase">
                    {t('Wedstrijdkaart')}
                </h2>
                <p className="font-serif text-base text-choc3 italic">
                    {card.has_result
                        ? t(
                              'Met de officiële uitslag. Deel ze met je vrienden of bewaar ze als herinnering.',
                          )
                        : t(
                              'Deel de kaart nu al, of na de wedstrijd met de uitslag.',
                          )}
                </p>
            </div>

            <div className="flex border border-gold/25">
                {(['story', 'post'] as const).map((option) => (
                    <button
                        key={option}
                        type="button"
                        onClick={() => setFormat(option)}
                        aria-pressed={format === option}
                        className={cn(
                            'flex-1 cursor-pointer px-3 py-2 font-sans text-[9px] tracking-[0.18em] uppercase transition-colors',
                            format === option
                                ? 'bg-choc text-cream'
                                : 'text-stone hover:bg-gold/10',
                        )}
                    >
                        {option === 'story' ? t('Story') : t('Post')}
                    </button>
                ))}
            </div>

            <img
                key={imageUrl}
                src={imageUrl}
                alt={t('Wedstrijdkaart')}
                loading="lazy"
                className={cn(
                    'mx-auto w-full border border-gold/15 bg-choc2 object-cover',
                    format === 'story'
                        ? 'aspect-[9/16] max-w-[280px]'
                        : 'aspect-[4/5] max-w-[360px]',
                )}
            />

            <div className="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    onClick={handleShare}
                    disabled={sharing}
                    className={cn(solidButton, 'col-span-2')}
                >
                    {t('Deel je kaart')}
                </button>
                <a href={downloadUrl} className={outlineButton}>
                    {t('Download')}
                </a>
                <button
                    type="button"
                    onClick={() => copy(card.share_url)}
                    className={outlineButton}
                >
                    {copiedText ? t('Link gekopieerd') : t('Kopieer link')}
                </button>
            </div>
        </div>
    );
}

/**
 * The public session link for everyone who is not playing: no card, just
 * the page with the roster and result.
 */
export function CopyShareLinkButton({
    url,
    className,
}: {
    url: string;
    className?: string;
}) {
    const { t } = useTranslation();
    const [copiedText, copy] = useClipboard();

    return (
        <button
            type="button"
            onClick={() => copy(url)}
            className={cn(
                'cursor-pointer border border-gold/30 bg-transparent px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10',
                className,
            )}
        >
            {copiedText ? t('Link gekopieerd') : t('Kopieer link')}
        </button>
    );
}

const solidButton =
    'cursor-pointer border-none bg-choc px-5 py-3.5 text-center font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 disabled:cursor-wait disabled:opacity-60';

const outlineButton =
    'cursor-pointer border border-gold/30 bg-transparent px-5 py-3.5 text-center font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase no-underline transition-colors hover:border-gold hover:bg-gold/10';
