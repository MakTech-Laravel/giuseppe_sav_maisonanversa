import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Building2,
    CalendarCheck,
    CalendarDays,
    CircleHelp,
    MessageSquareQuote,
    FileText,
    Globe,
    KeyRound,
    LayoutGrid,
    Mail,
    MapPin,
    MessageCircle,
    Package,
    Settings2,
    Shield,
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
import adminJournal from '@/routes/admin/journal';
import adminLegalPages from '@/routes/admin/legal-pages';
import adminLetter from '@/routes/admin/letter';
import adminOrders from '@/routes/admin/orders';
import adminPartnerClubs from '@/routes/admin/partner-clubs';
import adminPermissions from '@/routes/admin/permissions';
import adminProducts from '@/routes/admin/products';
import adminRoles from '@/routes/admin/roles';
import adminSeoMetas from '@/routes/admin/seo-metas';
import adminSiteSettings from '@/routes/admin/site-settings';
import { PERMISSIONS } from '@/types/permissions';

type NavGroup = {
    label: string;
    items: NavNode[];
};

function buildNavGroups(
    locale: string,
    t: (key: string) => string,
): NavGroup[] {
    return [
        {
            label: t('Overzicht'),
            items: [
                {
                    title: t('Dashboard'),
                    href: dashboard(locale),
                    icon: LayoutGrid,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
            ],
        },
        {
            label: t('Mensen'),
            items: [
                {
                    title: t('Beheerder'),
                    href: adminAdmins.index(locale),
                    icon: UserRoundCog,
                    permissions: [PERMISSIONS.USERS.INDEX],
                },
                {
                    title: t('Klanten'),
                    href: adminCustomers.index(locale),
                    icon: Users,
                    permissions: [PERMISSIONS.USERS.INDEX],
                },
                {
                    title: t('Rollen'),
                    href: adminRoles.index(locale),
                    icon: Shield,
                    permissions: [PERMISSIONS.ROLES.INDEX],
                },
                {
                    title: t('Rechten'),
                    href: adminPermissions.index(locale),
                    icon: KeyRound,
                    permissions: [PERMISSIONS.PERMISSIONS.INDEX],
                },
            ],
        },
        {
            label: t('Handel'),
            items: [
                {
                    title: t('Bestellingen'),
                    href: adminOrders.index(locale),
                    icon: ShoppingBag,
                    permissions: [PERMISSIONS.ORDERS.MANAGE],
                },
                {
                    title: t('Product'),
                    href: adminProducts.index(locale),
                    icon: Store,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t('Editievoorraad'),
                    href: adminHeritage.index(locale),
                    icon: Package,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
            ],
        },
        {
            label: t('Gemeenschap'),
            items: [
                {
                    title: t('Evenementen'),
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
                    title: t('Partner Clubs'),
                    href: adminPartnerClubs.index(locale),
                    icon: MapPin,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t('Clubs'),
                    href: adminClubs.index(locale),
                    icon: Building2,
                    permissions: [PERMISSIONS.CLUBS.MANAGE],
                },
                {
                    title: t('Sessies'),
                    href: adminSessions.index(locale),
                    icon: CalendarDays,
                    permissions: [PERMISSIONS.SESSIONS.MANAGE],
                },
                {
                    title: t('Gemeenschap'),
                    href: adminCommunity.index(locale),
                    icon: MessageCircle,
                    permissions: [PERMISSIONS.COMMUNITY.MODERATE],
                },
            ],
        },
        {
            label: t('Inhoud'),
            items: [
                {
                    title: t('FAQ'),
                    href: adminFaqs.index(locale),
                    icon: CircleHelp,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t('Afspraken'),
                    href: adminAppointments.index(locale),
                    icon: CalendarCheck,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t('Feedback'),
                    href: adminFeedback.index(locale),
                    icon: MessageSquareQuote,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t('Kleedkamer'),
                    href: adminDressingItems.index(locale),
                    icon: Shirt,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
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
                    permissions: [PERMISSIONS.POSTS.VIEW],
                },
            ],
        },
        {
            label: t('Website'),
            items: [
                {
                    title: t('Site-instellingen'),
                    href: adminSiteSettings.edit(locale),
                    icon: Globe,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t("Juridische Pagina's"),
                    href: adminLegalPages.index(locale),
                    icon: FileText,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
                {
                    title: t('SEO Meta'),
                    href: adminSeoMetas.index(locale),
                    icon: Globe,
                    permissions: [PERMISSIONS.HERITAGE.VIEW],
                },
            ],
        },
        {
            label: t('Account'),
            items: [
                {
                    // Parent `/settings` keeps this active on both profile and security.
                    title: t('Profiel & Beveiliging'),
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
    const navGroups = useMemo(() => buildNavGroups(locale, t), [locale, t]);

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
