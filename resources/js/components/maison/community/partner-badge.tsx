import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';

export function PartnerBadge({ className }: { className?: string }) {
    const { t } = useTranslation();

    return (
        <span
            className={cn(
                'inline-flex shrink-0 items-center border border-gold/25 bg-gold/8 px-2.5 py-1 font-sans text-[9px] tracking-[0.14em] text-gold2 uppercase',
                className,
            )}
        >
            {t('Maison Anversa Partner Club')}
        </span>
    );
}
