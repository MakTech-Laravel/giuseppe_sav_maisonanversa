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
import { dashboard } from '@/routes';
import adminPermissions from '@/routes/admin/permissions';
import adminRoles from '@/routes/admin/roles';
import adminUsers from '@/routes/admin/users';
import { index as fileUploadDemo } from '@/routes/file-upload-demo';
import type { NavItem } from '@/types';
import { PERMISSIONS } from '@/types/permissions';

// Navigation tree — supports nesting to any depth, per-item permission gates,
// badges, disabled/external links. Groups expand inline (or as a flyout when
// the sidebar is collapsed to icons).
const mainNav: NavNode[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        permissions: [PERMISSIONS.DASHBOARD.VIEW],
    },
    {
        title: 'File Upload Demo',
        href: fileUploadDemo(),
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
                href: adminUsers.index(),
                icon: Users,
                permissions: [PERMISSIONS.USERS.INDEX],
            },
            {
                title: 'Roles',
                href: adminRoles.index(),
                icon: Shield,
                permissions: [PERMISSIONS.ROLES.INDEX],
            },
            {
                title: 'Permissions',
                href: adminPermissions.index(),
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

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
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
