import { Link } from '@inertiajs/react';
import {
    BookOpen,
    FolderGit2,
    KeyRound,
    LayoutGrid,
    LockKeyhole,
    Settings,
    Shield,
    ShieldCheck,
    Upload,
    User,
    Users,
} from 'lucide-react';
import { useMemo } from 'react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
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
import adminPermissions from '@/routes/admin/permissions';
import adminRoles from '@/routes/admin/roles';
import adminUsers from '@/routes/admin/users';
import { index as fileUploadDemo } from '@/routes/file-upload-demo';
import type { NavItem } from '@/types';
import { PERMISSIONS } from '@/types/permissions';

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

function buildMainNav(locale: string): NavNode[] {
    return [
        {
            title: 'Dashboard',
            href: dashboard(locale),
            icon: LayoutGrid,
            permissions: [PERMISSIONS.DASHBOARD.VIEW],
        },
        {
            title: 'File Upload Demo',
            href: fileUploadDemo(locale),
            icon: Upload,
            permissions: [PERMISSIONS.FILE_UPLOAD.INDEX],
        },
        {
            title: 'Access Control',
            icon: ShieldCheck,
            permissions: [
                PERMISSIONS.USERS.INDEX,
                PERMISSIONS.ROLES.INDEX,
                PERMISSIONS.PERMISSIONS.INDEX,
            ],
            items: [
                {
                    title: 'Users',
                    href: adminUsers.index(locale),
                    icon: Users,
                    permissions: [PERMISSIONS.USERS.INDEX],
                },
                {
                    title: 'Roles',
                    href: adminRoles.index(locale),
                    icon: Shield,
                    permissions: [PERMISSIONS.ROLES.INDEX],
                },
                {
                    title: 'Permissions',
                    href: adminPermissions.index(locale),
                    icon: KeyRound,
                    permissions: [PERMISSIONS.PERMISSIONS.INDEX],
                },
                {
                    title: 'Settings',
                    icon: Settings,
                    permissions: [PERMISSIONS.SETTINGS.INDEX],
                    classNames: {
                        icon: 'size-5',
                    },
                    items: [
                        {
                            title: 'Profile',
                            href: '#',
                            icon: User,
                            permissions: [PERMISSIONS.SETTINGS.INDEX],
                        },
                        {
                            title: 'Security',
                            icon: Shield,
                            permissions: [PERMISSIONS.SETTINGS.INDEX],
                            items: [
                                {
                                    title: 'Password',
                                    href: '#',
                                    icon: LockKeyhole,
                                    permissions: [PERMISSIONS.SETTINGS.INDEX],
                                },
                            ],
                        },
                    ],
                },
            ],
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
