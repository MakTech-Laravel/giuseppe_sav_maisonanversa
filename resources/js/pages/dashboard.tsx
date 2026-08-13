import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    FileText,
    KeyRound,
    Shield,
    UserRoundCog,
    Users,
} from 'lucide-react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermission } from '@/hooks/use-permissions';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import admins from '@/routes/admin/admins';
import customers from '@/routes/admin/customers';
import permissions from '@/routes/admin/permissions';
import posts from '@/routes/admin/posts';
import roles from '@/routes/admin/roles';
import { PERMISSIONS } from '@/types/permissions';

interface DashboardProps {
    stats: { label: string; value: string; hint: string }[];
    recentCustomers: {
        id: number;
        name: string;
        email: string;
        username: string | null;
        created_at: string;
    }[];
    staffName: string;
}

export default function Dashboard({
    stats,
    recentCustomers,
    staffName,
}: DashboardProps) {
    const { can } = usePermission();
    const quickLinks = [
        {
            label: 'Customers',
            description: 'Manage member accounts',
            href: customers.index(wayfinderLocale()),
            icon: Users,
            permission: PERMISSIONS.USERS.INDEX,
        },
        {
            label: 'Admins',
            description: 'Manage staff access',
            href: admins.index(wayfinderLocale()),
            icon: UserRoundCog,
            permission: PERMISSIONS.USERS.INDEX,
        },
        {
            label: 'Roles',
            description: 'Configure access roles',
            href: roles.index(wayfinderLocale()),
            icon: Shield,
            permission: PERMISSIONS.ROLES.INDEX,
        },
        {
            label: 'Permissions',
            description: 'Review permission keys',
            href: permissions.index(wayfinderLocale()),
            icon: KeyRound,
            permission: PERMISSIONS.PERMISSIONS.INDEX,
        },
        {
            label: 'Posts',
            description: 'Manage content entries',
            href: posts.index(wayfinderLocale()),
            icon: FileText,
            permission: PERMISSIONS.POSTS.INDEX,
        },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={`Welcome${staffName ? `, ${staffName}` : ''}`}
                    description="An overview of Maison Anversa operations."
                    icon={Shield}
                />

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map((stat) => (
                        <Card key={stat.label}>
                            <CardHeader className="pb-2">
                                <CardDescription>{stat.label}</CardDescription>
                                <CardTitle className="text-3xl tabular-nums">
                                    {stat.value}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="text-xs text-muted-foreground">
                                {stat.hint}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-6 xl:grid-cols-[1fr_2fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Quick links</CardTitle>
                            <CardDescription>
                                Jump to common administration areas.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-2">
                            {quickLinks
                                .filter((item) => can(item.permission))
                                .map((item) => (
                                    <Button
                                        key={item.label}
                                        variant="outline"
                                        className="h-auto justify-start gap-3 px-3 py-3"
                                        asChild
                                    >
                                        <Link href={item.href}>
                                            <item.icon className="h-4 w-4 text-primary" />
                                            <span className="text-left">
                                                <span className="block font-medium">
                                                    {item.label}
                                                </span>
                                                <span className="block text-xs font-normal text-muted-foreground">
                                                    {item.description}
                                                </span>
                                            </span>
                                            <ArrowRight className="ml-auto h-4 w-4" />
                                        </Link>
                                    </Button>
                                ))}
                        </CardContent>
                    </Card>

                    <Card className="overflow-hidden">
                        <CardHeader>
                            <CardTitle>Recent customers</CardTitle>
                            <CardDescription>
                                The latest member accounts.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Customer</TableHead>
                                        <TableHead className="hidden md:table-cell">
                                            Username
                                        </TableHead>
                                        <TableHead className="hidden sm:table-cell">
                                            Joined
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentCustomers.map((customer) => (
                                        <TableRow key={customer.id}>
                                            <TableCell>
                                                <Link
                                                    href={customers.show({
                                                        locale: wayfinderLocale(),
                                                        user: customer.id,
                                                    })}
                                                    className="font-medium hover:text-primary"
                                                >
                                                    {customer.name}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {customer.email}
                                                </p>
                                            </TableCell>
                                            <TableCell className="hidden text-muted-foreground md:table-cell">
                                                {customer.username ?? '—'}
                                            </TableCell>
                                            <TableCell className="hidden text-muted-foreground sm:table-cell">
                                                {new Date(
                                                    customer.created_at,
                                                ).toLocaleDateString()}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            {recentCustomers.length === 0 && (
                                <p className="p-8 text-center text-sm text-muted-foreground">
                                    No customers yet.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(wayfinderLocale()),
        },
    ],
};
