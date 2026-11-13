import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BellRing,
    BookOpen,
    Building2,
    CalendarCheck,
    CalendarDays,
    CircleHelp,
    DoorOpen,
    MessageSquareQuote,
    FileText,
    Globe,
    ImageIcon,
    ScrollText,
    LayoutGrid,
    Mail,
    MessageCircle,
    MessagesSquare,
    Package,
    RotateCcw,
    ServerCog,
    Settings2,
    ShoppingBag,
    Shirt,
    Store,
    UserRoundCog,
    Users,
    UsersRound,
} from 'lucide-react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import AppLogo from '@/components/app-logo';
import { NavUser } from '@/components/nav-user';
import { SidebarNav } from '@/components/navigation';
import type { NavNode } from '@/components/navigation';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useLocale } from '@/hooks/use-locale';
import { dashboard } from '@/routes/admin';
import adminAdmins from '@/routes/admin/admins';
import adminAppointments from '@/routes/admin/appointments';
import adminChat from '@/routes/admin/chat';
import adminCircle from '@/routes/admin/circle';
import adminClubs from '@/routes/admin/clubs';
import adminCommunity from '@/routes/admin/community';
import adminSessions from '@/routes/admin/community-sessions';
import adminCustomers from '@/routes/admin/customers';
import adminDressingItems from '@/routes/admin/dressing-items';
import adminEvents from '@/routes/admin/events';
import adminFaqs from '@/routes/admin/faqs';
import adminFeedback from '@/routes/admin/feedback';
import adminHeritage from '@/routes/admin/heritage';
import adminHomeHero from '@/routes/admin/home-hero';
import adminJournal from '@/routes/admin/journal';
import adminLegalPages from '@/routes/admin/legal-pages';
import adminLetter from '@/routes/admin/letter';
import adminNotifications from '@/routes/admin/notifications';
import adminNotificationTest from '@/routes/admin/notifications/test';
import adminOpeningOffers from '@/routes/admin/opening-offers';
import adminOrders from '@/routes/admin/orders';
import adminProducts from '@/routes/admin/products';
import adminServersMaintenance from '@/routes/admin/servers-maintenance';
import adminSeoMetas from '@/routes/admin/seo-metas';
import adminSiteSettings from '@/routes/admin/site-settings';
import adminStorefrontAccess from '@/routes/admin/storefront-access';
import adminStoryPage from '@/routes/admin/story-page';
import { PERMISSIONS } from '@/types/permissions';

type NavGroup = {
    label: string;
    items: NavNode[];
};

function buildNavGroups(
    locale: string,
    t: (key: string) => string,
    returnsOpenCount = 0,
): NavGroup[] {
    return [
        {
            label: t('Overview'),
            items: [
                {
                    title: t('Dashboard'),
                    href: dashboard(locale),
                    icon: LayoutGrid,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Notifications'),
                    href: adminNotifications.index(locale),
                    icon: Bell,
                },
                {
                    title: t('Test notifications'),
                    href: adminNotificationTest.index(locale),
                    icon: BellRing,
                    permissions: [PERMISSIONS.NOTIFICATIONS.TEST],
                },
                {
                    title: t('Reverb Server'),
                    href: adminServersMaintenance.index(locale),
                    icon: ServerCog,
                    // Read-always for admins: visible whenever the admin shell is reachable.
                },
            ],
        },
        {
            label: t('People'),
            items: [
                {
                    title: t('Admin'),
                    href: adminAdmins.index(locale),
                    icon: UserRoundCog,
                    permissions: [PERMISSIONS.USERS.INDEX],
                },
                {
                    title: t('Customers'),
                    href: adminCustomers.index(locale),
                    icon: Users,
                    permissions: [PERMISSIONS.USERS.INDEX],
                },
            ],
        },
        {
            label: t('Commerce'),
            items: [
                {
                    title: t('Orders'),
                    href: adminOrders.index(locale),
                    icon: ShoppingBag,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Returns'),
                    href: `/${locale}/admin/returns`,
                    icon: RotateCcw,
                    permissions: [PERMISSIONS.RETURNS.MANAGE],
                    badge: returnsOpenCount > 0 ? returnsOpenCount : undefined,
                },
                {
                    title: t('Product'),
                    href: adminProducts.index(locale),
                    icon: Store,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Edition inventory'),
                    href: adminHeritage.index(locale),
                    icon: Package,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
            ],
        },
        {
            label: t('Community'),
            items: [
                {
                    title: t('Events'),
                    href: adminEvents.index(locale),
                    icon: CalendarDays,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Founding Circle'),
                    href: adminCircle.index(locale),
                    icon: UsersRound,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Clubs'),
                    href: adminClubs.index(locale),
                    icon: Building2,
                    permissions: [PERMISSIONS.CLUBS.MANAGE],
                },
                {
                    title: t('Sessions'),
                    href: adminSessions.index(locale),
                    icon: CalendarDays,
                    permissions: [PERMISSIONS.SESSIONS.MANAGE],
                },
                {
                    title: t('Chat'),
                    href: adminChat.index(locale),
                    icon: MessagesSquare,
                    permissions: [PERMISSIONS.SESSIONS.MANAGE],
                },
                {
                    title: t('Community'),
                    href: adminCommunity.index(locale),
                    icon: MessageCircle,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
            ],
        },
        {
            label: t('Content'),
            items: [
                {
                    title: t('FAQ'),
                    href: adminFaqs.index(locale),
                    icon: CircleHelp,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Appointments'),
                    href: adminAppointments.index(locale),
                    icon: CalendarCheck,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Feedback'),
                    href: adminFeedback.index(locale),
                    icon: MessageSquareQuote,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Dressing Room'),
                    href: adminDressingItems.index(locale),
                    icon: Shirt,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Heritage Letter'),
                    href: adminLetter.index(locale),
                    icon: Mail,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Journal'),
                    href: adminJournal.index(locale),
                    icon: BookOpen,
                    permissions: [
                        PERMISSIONS.POSTS.VIEW,
                        PERMISSIONS.POSTS.INDEX,
                    ],
                },
            ],
        },
        {
            label: t('Website'),
            items: [
                {
                    title: t('Home hero'),
                    href: adminHomeHero.edit(locale),
                    icon: ImageIcon,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t('Opening offer'),
                    href: adminOpeningOffers.index(locale),
                    icon: DoorOpen,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t('Our story'),
                    href: adminStoryPage.edit(locale),
                    icon: ScrollText,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t('Site settings'),
                    href: adminSiteSettings.edit(locale),
                    icon: Globe,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Storefront access'),
                    href: adminStorefrontAccess.edit(locale),
                    icon: Store,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t("Legal Pages"),
                    href: adminLegalPages.index(locale),
                    icon: FileText,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('SEO Meta'),
                    href: adminSeoMetas.index(locale),
                    icon: Globe,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
            ],
        },
        {
            label: t('Account'),
            items: [
                {
                    // Parent `/settings` keeps this active on both profile and security.
                    title: t('Profile & Security'),
                    href: `/${locale}/settings`,
                    icon: Settings2,
                },
            ],
        },
    ];
}

export function AppSidebar() {
    const { locale } = useLocale();
    const { t } = useTranslation();
    const returnsOpenCount =
        (usePage().props.returns as { openCount?: number } | undefined)
            ?.openCount ?? 0;
    const navGroups = useMemo(
        () => buildNavGroups(locale, t, returnsOpenCount),
        [locale, t, returnsOpenCount],
    );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard(locale)} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group) => (
                    <SidebarNav
                        key={group.label}
                        items={group.items}
                        label={group.label}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
