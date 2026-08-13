import { Link } from '@inertiajs/react';
import {
    FileText,
    LayoutGrid,
    LockKeyhole,
    Mail,
    MessageCircle,
    ShoppingBag,
    User,
    UserRoundCog,
    Users,
} from 'lucide-react';
import { useMemo } from 'react';
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
import adminCommunity from '@/routes/admin/community';
import adminCustomers from '@/routes/admin/customers';
import adminLetter from '@/routes/admin/letter';
import adminOrders from '@/routes/admin/orders';
import adminPosts from '@/routes/admin/posts';
import profile from '@/routes/profile';
import security from '@/routes/security';
import { PERMISSIONS } from '@/types/permissions';

function buildMainNav(locale: string): NavNode[] {
    return [
        {
            title: 'Dashboard',
            href: dashboard(locale),
            icon: LayoutGrid,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
        {
            title: 'Administrator',
            href: adminAdmins.index(locale),
            icon: UserRoundCog,
            permissions: [PERMISSIONS.USERS.INDEX],
        },
        {
            title: 'Customers',
            href: adminCustomers.index(locale),
            icon: Users,
            permissions: [PERMISSIONS.USERS.INDEX],
        },
        {
            title: 'Orders',
            href: adminOrders.index(locale),
            icon: ShoppingBag,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
        {
            title: 'Community',
            href: adminCommunity.index(locale),
            icon: MessageCircle,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
        {
            title: 'Heritage Letter',
            href: adminLetter.index(locale),
            icon: Mail,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
        {
            title: 'Posts',
            href: adminPosts.index(locale),
            icon: FileText,
            permissions: [PERMISSIONS.POSTS.VIEW, PERMISSIONS.POSTS.INDEX],
        },
        // Access Control (roles / permissions UI) disabled — out of product scope.
        {
            title: 'Profile',
            href: profile.edit(locale),
            icon: User,
        },
        {
            title: 'Security',
            href: security.edit(locale),
            icon: LockKeyhole,
        },
    ];
}

export function AppSidebar() {
    const { locale } = useLocale();
    const mainNav = useMemo(() => buildMainNav(locale), [locale]);

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
                <SidebarNav items={mainNav} label="Platform" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
