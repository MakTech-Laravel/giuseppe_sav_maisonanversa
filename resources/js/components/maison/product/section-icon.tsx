import { Icon, isValidLucideIconKey } from '@/lib/icons';
import { cn } from '@/lib/utils';

type SectionIconProps = {
    icon?: string | null;
    className?: string;
};

/**
 * Renders a Lucide icon when the stored value is a valid key; otherwise falls
 * back to the legacy Unicode glyph / free-text display.
 */
export function SectionIcon({ icon, className }: SectionIconProps) {
    if (!icon) {
        return null;
    }

    if (isValidLucideIconKey(icon)) {
        return (
            <Icon
                icon={icon}
                className={cn('mx-auto size-[1em]', className)}
                aria-hidden
            />
        );
    }

    return (
        <span aria-hidden="true" className={className}>
            {icon}
        </span>
    );
}
