import { Head, Link, WhenVisible, router, usePage } from '@inertiajs/react';
import {
    Calendar,
    Eye,
    Mail,
    Pencil,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
    UserCheck,
    Users,
    UsersRound,
} from 'lucide-react';
import { motion } from 'motion/react';
import { useEffect, useRef, useState } from 'react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { DataPagination } from '@/components/admin/data-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
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
import { avatarUrl, SUPER_ADMIN_ROLE } from '@/types/admin';
import type { AdminUser, Paginated } from '@/types/admin';
import { PERMISSIONS } from '@/types/permissions';

interface AdminsIndexProps {
    users: Paginated<AdminUser>;
    roles: string[];
    filters: { search: string; role: string };
    superAdminCount: number;
    stats?: { total: number; verified: number; roles: number };
}

const ALL_ROLES = 'all';

export default function AdminsIndex({
    users: paginated,
    roles,
    filters,
    superAdminCount,
    stats,
}: AdminsIndexProps) {
    const { can } = usePermission();
    const actorIsSuperAdmin =
        usePage().props.auth.user?.is_super_admin ?? false;
    const [search, setSearch] = useState(filters.search ?? '');
    const [role, setRole] = useState(filters.role || ALL_ROLES);
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                admins.index(wayfinderLocale()).url,
                {
                    search: search || undefined,
                    role: role === ALL_ROLES ? undefined : role,
                },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search, role]);

    return (
        <>
            <Head title="Admins" />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title="Admins"
                    description="Manage staff accounts, roles, and platform access."
                    icon={Users}
                >
                    {can(PERMISSIONS.USERS.CREATE) && (
                        <Button asChild>
                            <Link href={admins.create(wayfinderLocale())}>
                                <Plus className="h-4 w-4" /> Add admin
                            </Link>
                        </Button>
                    )}
                </AdminPageHeader>

                <WhenVisible
                    data="stats"
                    fallback={
                        <div className="grid gap-4 sm:grid-cols-3">
                            {[0, 1, 2].map((item) => (
                                <Skeleton
                                    key={item}
                                    className="h-24 rounded-xl"
                                />
                            ))}
                        </div>
                    }
                >
                    {stats && (
                        <motion.div
                            initial={{ opacity: 0, y: 8 }}
                            animate={{ opacity: 1, y: 0 }}
                            className="grid gap-4 sm:grid-cols-3"
                        >
                            <StatCard
                                label="Total admins"
                                value={stats.total}
                                icon={UsersRound}
                            />
                            <StatCard
                                label="Verified"
                                value={stats.verified}
                                icon={UserCheck}
                            />
                            <StatCard
                                label="Roles"
                                value={stats.roles}
                                icon={ShieldCheck}
                            />
                        </motion.div>
                    )}
                </WhenVisible>

                <div className="flex flex-col gap-3 sm:flex-row">
                    <div className="relative w-full sm:max-w-xs">
                        <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search admins…"
                            className="pl-9"
                        />
                    </div>
                    <Select value={role} onValueChange={setRole}>
                        <SelectTrigger className="w-full sm:w-48">
                            <SelectValue placeholder="Filter by role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL_ROLES}>All roles</SelectItem>
                            {roles.map((item) => (
                                <SelectItem
                                    key={item}
                                    value={item}
                                    className="capitalize"
                                >
                                    {item}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50">
                                    <TableHead>Admin</TableHead>
                                    <TableHead className="hidden md:table-cell">
                                        Roles
                                    </TableHead>
                                    <TableHead className="hidden lg:table-cell">
                                        Joined
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {paginated.data.map((admin) => {
                                    const targetIsSuperAdmin = admin.roles.some(
                                        (item) =>
                                            item.name === SUPER_ADMIN_ROLE,
                                    );
                                    const canManage =
                                        !targetIsSuperAdmin ||
                                        actorIsSuperAdmin;
                                    const lockDelete =
                                        targetIsSuperAdmin &&
                                        superAdminCount <= 1;

                                    return (
                                        <TableRow key={admin.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <Avatar admin={admin} />
                                                    <div className="min-w-0">
                                                        <span className="block truncate font-medium">
                                                            {admin.name}
                                                        </span>
                                                        <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                                            <Mail className="h-3 w-3" />
                                                            {admin.email}
                                                        </span>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <div className="flex flex-wrap gap-1">
                                                    {admin.roles.map((item) => (
                                                        <Badge
                                                            key={item.id}
                                                            variant="secondary"
                                                            className="capitalize"
                                                        >
                                                            {item.name}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                                    <Calendar className="h-3.5 w-3.5" />
                                                    {new Date(
                                                        admin.created_at,
                                                    ).toLocaleDateString()}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-1">
                                                    {can(
                                                        PERMISSIONS.USERS.VIEW,
                                                    ) && (
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Link
                                                                href={admins.show(
                                                                    {
                                                                        locale: wayfinderLocale(),
                                                                        user: admin.id,
                                                                    },
                                                                )}
                                                                title="View"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                                    {can(
                                                        PERMISSIONS.USERS.EDIT,
                                                    ) &&
                                                        canManage && (
                                                            <Button
                                                                asChild
                                                                variant="ghost"
                                                                size="icon"
                                                            >
                                                                <Link
                                                                    href={admins.edit(
                                                                        {
                                                                            locale: wayfinderLocale(),
                                                                            user: admin.id,
                                                                        },
                                                                    )}
                                                                    title="Edit"
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                        )}
                                                    {can(
                                                        PERMISSIONS.USERS
                                                            .DELETE,
                                                    ) &&
                                                        canManage &&
                                                        !lockDelete && (
                                                            <ConfirmDeleteDialog
                                                                description={`Permanently delete ${admin.name} and all associated data?`}
                                                                onConfirm={() =>
                                                                    router.delete(
                                                                        admins.destroy(
                                                                            {
                                                                                locale: wayfinderLocale(),
                                                                                user: admin.id,
                                                                            },
                                                                        ).url,
                                                                    )
                                                                }
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="text-muted-foreground hover:text-destructive"
                                                                    title="Delete"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </ConfirmDeleteDialog>
                                                        )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                        {paginated.data.length === 0 && (
                            <div className="px-4 py-16 text-center text-sm text-muted-foreground">
                                No admins found.
                            </div>
                        )}
                    </div>
                    <DataPagination meta={paginated} />
                </div>
            </div>
        </>
    );
}

function Avatar({ admin }: { admin: AdminUser }) {
    const url = avatarUrl(admin.avatar);

    return (
        <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-secondary font-semibold">
            {url ? (
                <img
                    src={url}
                    alt={admin.name}
                    className="h-full w-full object-cover"
                />
            ) : (
                admin.name.charAt(0).toUpperCase()
            )}
        </div>
    );
}

function StatCard({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: number;
    icon: typeof Users;
}) {
    return (
        <div className="flex items-center gap-4 rounded-xl border bg-card p-5 shadow-sm">
            <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Icon className="h-5 w-5" />
            </div>
            <div>
                <p className="text-2xl font-bold tabular-nums">{value}</p>
                <p className="text-xs text-muted-foreground">{label}</p>
            </div>
        </div>
    );
}

AdminsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Admins', href: admins.index(wayfinderLocale()) },
    ],
};
