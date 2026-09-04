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
    LayoutGrid,
    Mail,
    MessageCircle,
    Package,
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
import adminCircle from '@/routes/admin/circle';
import adminClubs from '@/routes/admin/clubs';
import adminSiteSettings from '@/routes/admin/site-settings';
import adminCommunity from '@/routes/admin/community';
import adminCustomers from '@/routes/admin/customers';
import adminDressingItems from '@/routes/admin/dressing-items';
import adminEvents from '@/routes/admin/events';
import adminAppointments from '@/routes/admin/appointments';
import adminFaqs from '@/routes/admin/faqs';
import adminFeedback from '@/routes/admin/feedback';
import adminHeritage from '@/routes/admin/heritage';
import adminJournal from '@/routes/admin/journal';
import adminLegalPages from '@/routes/admin/legal-pages';
import adminLetter from '@/routes/admin/letter';
import adminOrders from '@/routes/admin/orders';
import adminProducts from '@/routes/admin/products';
import adminSeoMetas from '@/routes/admin/seo-metas';
import adminSessions from '@/routes/admin/community-sessions';
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
            ],
        },
        {
            label: t('Handel'),
            items: [
                {
                    title: t('Bestellingen'),
                    href: adminOrders.index(locale),
                    icon: ShoppingBag,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Product'),
                    href: adminProducts.index(locale),
                    icon: Store,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Editievoorraad'),
                    href: adminHeritage.index(locale),
                    icon: Package,
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
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
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
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
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Afspraken'),
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
                    title: t('Kleedkamer'),
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
                    permissions: [PERMISSIONS.POSTS.VIEW, PERMISSIONS.POSTS.INDEX],
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
                    permissions: [PERMISSIONS.DASHBOARD.VIEW],
                },
                {
                    title: t('Juridische Pagina\'s'),
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
