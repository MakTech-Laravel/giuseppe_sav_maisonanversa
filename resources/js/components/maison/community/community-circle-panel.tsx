import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { FeedSidebar } from '@/components/maison/community/feed-sidebar';
import { Monogram } from '@/components/maison/ui/monogram';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

type CommunityCirclePanelProps = {
    onViewEvents: () => void;
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

/**
 * Always-on Circle access: a fixed control that opens the profile / members
 * panel as a sheet, so members never have to scroll back to the top of the feed.
 */
export function CommunityCirclePanel({
    onViewEvents,
}: CommunityCirclePanelProps) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const [open, setOpen] = useState(false);
    const name = auth.user?.name ?? 'Member';
    const initials = initialsFromName(name);

    function handleViewEvents() {
        setOpen(false);
        onViewEvents();
    }

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <button
                    type="button"
                    className="fixed bottom-6 left-5 z-40 flex items-center gap-3 border border-gold/35 bg-choc px-4 py-3 text-left shadow-[0_12px_40px_rgba(41,28,24,0.35)] transition-colors hover:border-gold hover:bg-gold2 md:bottom-8 md:left-8"
                    aria-label={t('Open Circle')}
                >
                    <Monogram
                        initials={initials}
                        size="sm"
                        emphasis
                        className="border-gold"
                    />
                    <span className="hidden pr-1 sm:block">
                        <span className="block font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                            {t('Uw Circle')}
                        </span>
                        <span className="mt-0.5 block max-w-36 truncate font-serif text-sm text-cream">
                            {name}
                        </span>
                    </span>
                </button>
            </SheetTrigger>

            <SheetContent
                side="right"
                className="w-full gap-0 overflow-y-auto border-gold/20 bg-cream p-0 sm:max-w-md"
            >
                <SheetHeader className="border-b border-gold/15 bg-cream2 px-6 py-5 text-left">
                    <SheetTitle className="font-serif text-2xl font-medium text-choc">
                        {t('Uw Circle')}
                    </SheetTitle>
                    <SheetDescription className="font-sans text-[11px] tracking-[0.12em] text-stone uppercase">
                        {t('Profiel, leden en het volgende event')}
                    </SheetDescription>
                </SheetHeader>

                <div className="p-6">
                    <FeedSidebar onViewEvents={handleViewEvents} />
                </div>
            </SheetContent>
        </Sheet>
    );
}
