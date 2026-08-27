import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Wrap } from '@/components/maison/ui/section';
import * as eventRoutes from '@/routes/community/events';
import * as sessionRoutes from '@/routes/community/sessions';
import * as maison from '@/routes/maison';
import { cn } from '@/lib/utils';

export type CommunitySection = 'feed' | 'courts' | 'sessions' | 'events';

type TabLink = {
    id: CommunitySection;
    label: string;
    href: string;
};

const tabClassName =
    'relative shrink-0 px-5 py-4 font-sans text-[10px] tracking-[0.22em] uppercase transition-colors md:px-7';

const activeClassName =
    'font-medium text-choc after:absolute after:right-5 after:bottom-0 after:left-5 after:h-0.5 after:bg-choc md:after:right-7 md:after:left-7';

/**
 * Sticky community sub-nav. Every item is a real URL so Feed, Club Corners,
 * Sessions and Events can be bookmarked and shared.
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
            id: 'courts',
            label: 'Club Corners & banen',
            href: maison.community.url(locale, { query: { tab: 'courts' } }),
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
                    className="flex gap-1 overflow-x-auto overflow-y-hidden [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
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
    const [path, query = ''] = url.split('?');
    const params = new URLSearchParams(query);

    if (path.includes('/community/sessions')) {
        return 'sessions';
    }

    if (path.includes('/community/events')) {
        return 'events';
    }

    if (params.get('tab') === 'courts') {
        return 'courts';
    }

    return 'feed';
}
