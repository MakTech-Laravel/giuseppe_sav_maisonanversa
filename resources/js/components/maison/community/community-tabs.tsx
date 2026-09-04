import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Wrap } from '@/components/maison/ui/section';
import { cn } from '@/lib/utils';
import * as clubRoutes from '@/routes/community/clubs';
import * as eventRoutes from '@/routes/community/events';
import * as sessionRoutes from '@/routes/community/sessions';
import * as maison from '@/routes/maison';

export type CommunitySection = 'feed' | 'clubs' | 'sessions' | 'events';

type TabLink = {
    id: CommunitySection;
    label: string;
    href: string;
};

const tabClassName =
    'relative w-full shrink-0 px-5 py-3.5 font-sans text-[10px] tracking-[0.22em] uppercase transition-colors md:w-auto md:px-7 md:py-4';

const activeClassName =
    'font-medium text-choc after:absolute after:right-5 after:bottom-0 after:left-5 after:h-0.5 after:bg-choc md:after:right-7 md:after:left-7';

/**
 * Sticky community sub-nav. Every item is a real URL so Feed, Clubs,
 * Sessions and Events can be bookmarked and shared.
 *
 * Stacked vertically below md so long labels stay readable; horizontal scroll
 * row on larger screens.
 */
export function CommunityTabs() {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const { url } = usePage();
    const active = activeSection(url);

    const tabs: TabLink[] = [
        {
            id: 'feed',
            label: 'Feed',
            href: maison.community.url(locale),
        },
        {
            id: 'clubs',
            label: 'Clubs',
            href: clubRoutes.index.url(locale),
        },
        {
            id: 'sessions',
            label: 'Sessies',
            href: sessionRoutes.index.url(locale),
        },
        {
            id: 'events',
            label: 'Exclusieve Evenementen',
            href: eventRoutes.index.url(locale),
        },
    ];

    return (
        <div className="sticky top-[calc(var(--topbar-h)+var(--nav-h))] z-50 border-b border-gold/20 bg-cream">
            <Wrap className="px-6 md:px-10 lg:px-20">
                <div
                    role="tablist"
                    aria-label={t('Community')}
                    className="flex flex-col md:[scrollbar-width:none] md:flex-row md:gap-1 md:overflow-x-auto md:overflow-y-hidden md:[-ms-overflow-style:none] md:[&::-webkit-scrollbar]:hidden"
                >
                    {tabs.map((tab) => {
                        const isActive = tab.id === active;

                        return (
                            <Link
                                key={tab.id}
                                href={tab.href}
                                role="tab"
                                aria-selected={isActive}
                                preserveScroll
                                className={cn(
                                    tabClassName,
                                    isActive
                                        ? activeClassName
                                        : 'text-stone hover:text-choc',
                                )}
                            >
                                {t(tab.label)}
                            </Link>
                        );
                    })}
                </div>
            </Wrap>
        </div>
    );
}

export function activeSection(url: string): CommunitySection {
    const [path] = url.split('?');

    if (path.includes('/community/clubs')) {
        return 'clubs';
    }

    if (path.includes('/community/sessions')) {
        return 'sessions';
    }

    if (path.includes('/community/events')) {
        return 'events';
    }

    return 'feed';
}
