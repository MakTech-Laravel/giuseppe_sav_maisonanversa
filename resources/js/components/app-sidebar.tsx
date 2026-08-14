import { Link } from '@inertiajs/react';
import {
    CalendarDays,
    FileText,
    LayoutGrid,
    Mail,
    MessageCircle,
    Package,
    Settings2,
    ShoppingBag,
    Store,
    Truck,
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
import adminCommerce from '@/routes/admin/commerce';
import adminCommunity from '@/routes/admin/community';
import adminCustomers from '@/routes/admin/customers';
import adminEvents from '@/routes/admin/events';
import adminHeritage from '@/routes/admin/heritage';
import adminLetter from '@/routes/admin/letter';
import adminOrders from '@/routes/admin/orders';
import adminPosts from '@/routes/admin/posts';
import adminProducts from '@/routes/admin/products';
import { PERMISSIONS } from '@/types/permissions';

function buildMainNav(
    locale: string,
    t: (key: string) => string,
): NavNode[] {
    return [
        {
            title: t('Dashboard'),
            href: dashboard(locale),
            icon: LayoutGrid,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
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
            title: t('Bestellingen'),
            href: adminOrders.index(locale),
            icon: ShoppingBag,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
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
            title: t('Catalogus'),
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
        {
            title: t('Handelsinstellingen'),
            href: adminCommerce.edit(locale),
            icon: Truck,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
        {
            title: t('Gemeenschap'),
            href: adminCommunity.index(locale),
            icon: MessageCircle,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
        {
            title: t('Heritage Letter'),
            href: adminLetter.index(locale),
            icon: Mail,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
        {
            title: t('Berichten'),
            href: adminPosts.index(locale),
            icon: FileText,
            permissions: [PERMISSIONS.POSTS.VIEW, PERMISSIONS.POSTS.INDEX],
        },
        {
            // Parent `/settings` keeps this active on both profile and security.
            title: t('Profiel & Beveiliging'),
            href: `/${locale}/settings`,
            icon: Settings2,
        },
    ];
}

export function AppSidebar() {
    const { locale } = useLocale();
    const { t } = useTranslation();
    const mainNav = useMemo(() => buildMainNav(locale, t), [locale, t]);

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
                <SidebarNav items={mainNav} label={t('Platform')} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
