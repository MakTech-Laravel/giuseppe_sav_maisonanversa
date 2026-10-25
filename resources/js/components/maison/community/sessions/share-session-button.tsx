import { useTranslation } from 'react-i18next';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';

type ShareSessionButtonProps = {
    url: string;
    title?: string;
    className?: string;
};

/**
 * Native share on supporting mobile browsers; otherwise copy the session URL.
 */
export function ShareSessionButton({
    url,
    title,
    className,
}: ShareSessionButtonProps) {
    const { t } = useTranslation();
    const [copiedText, copy] = useClipboard();

    async function handleShare() {
        if (typeof navigator.share === 'function') {
            try {
                await navigator.share({
                    title: title ?? t('Sessie'),
                    url,
                });

                return;
            } catch {
                // User cancelled or share failed — fall through to copy.
            }
        }

        await copy(url);
    }

    return (
        <button
            type="button"
            onClick={() => {
                void handleShare();
            }}
            className={cn(
                'cursor-pointer border border-gold/30 bg-transparent px-7 py-3.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10',
                className,
            )}
        >
            {copiedText ? t('Link gekopieerd') : t('Deel')}
        </button>
    );
}
