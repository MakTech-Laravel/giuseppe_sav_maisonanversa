import { Head, Link, router } from '@inertiajs/react';
import {
    KeyRound,
    Pencil,
    Plus,
    Search,
    Shield,
    Trash2,
    Users as UsersIcon,
} from 'lucide-react';
import { AnimatePresence, motion } from 'motion/react';
import { useEffect, useRef, useState } from 'react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { DataPagination } from '@/components/admin/data-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import roles from '@/routes/admin/roles';
import { SUPER_ADMIN_ROLE } from '@/types/admin';
import type { AdminRoleListItem, Paginated } from '@/types/admin';
import { PERMISSIONS } from '@/types/permissions';

interface RolesIndexProps {
    roles: Paginated<AdminRoleListItem>;
    filters: { search: string };
}

export default function RolesIndex({
    roles: paginated,
    filters,
}: RolesIndexProps) {
    const { can } = usePermission();
    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                roles.index(wayfinderLocale()).url,
                { search: search || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search]);

    return (
        <>
            <Head title="Roles" />

            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title="Roles"
                    description="Define roles and the permissions they grant."
                    icon={Shield}
                >
                    {can(PERMISSIONS.ROLES.CREATE) && (
                        <Button asChild>
                            <Link href={roles.create(wayfinderLocale()).url}>
                                <Plus className="h-4 w-4" /> Add role
                            </Link>
                        </Button>
                    )}
                </AdminPageHeader>

                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search roles…"
                        className="pl-9"
                    />
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Role</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    Permissions
                                </TableHead>
                                <TableHead className="hidden md:table-cell">
                                    Users
                                </TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <AnimatePresence initial={false}>
                                {paginated.data.map((role) => {
                                    const isSuper =
                                        role.name === SUPER_ADMIN_ROLE;

                                    return (
                                        <motion.tr
                                            key={role.id}
                                            initial={{ opacity: 0 }}
                                            animate={{ opacity: 1 }}
                                            exit={{ opacity: 0 }}
                                            className="border-b transition-colors hover:bg-muted/40"
                                        >
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium capitalize">
                                                        {role.name}
                                                    </span>
                                                    {isSuper && (
                                                        <Badge variant="secondary">
                                                            System
                                                        </Badge>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden sm:table-cell">
                                                <span className="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                                                    <KeyRound className="h-3.5 w-3.5" />
                                                    {role.permissions_count}
                                                </span>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                <span className="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                                                    <UsersIcon className="h-3.5 w-3.5" />
                                                    {role.users_count}
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    {can(
                                                        PERMISSIONS.ROLES.EDIT,
                                                    ) && (
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Link
                                                                href={roles.edit(
                                                                    {
                                                                        locale: wayfinderLocale(),
                                                                        role: role.id,
                                                                    },
                                                                ).url}
                                                                title="Edit"
                                                            >
                                                                <Pencil className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                                    {can(
                                                        PERMISSIONS.ROLES
                                                            .DELETE,
                                                    ) &&
                                                        !isSuper && (
                                                            <ConfirmDeleteDialog
                                                                description={
                                                                    <>
                                                                        Delete
                                                                        the{' '}
                                                                        <strong className="capitalize">
                                                                            {
                                                                                role.name
                                                                            }
                                                                        </strong>{' '}
                                                                        role?
                                                                        Users
                                                                        with
                                                                        this
                                                                        role
                                                                        will
                                                                        lose its
                                                                        permissions.
                                                                    </>
                                                                }
                                                                onConfirm={() =>
                                                                    router.delete(
                                                                        roles.destroy(
                                                                            {
                                                                                locale: wayfinderLocale(),
                                                                                role: role.id,
                                                                            },
                                                                        ).url,
                                                                        {
                                                                            preserveScroll: true,
                                                                        },
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
                                        </motion.tr>
                                    );
                                })}
                            </AnimatePresence>
                        </TableBody>
                    </Table>

                    <DataPagination meta={paginated} />
                </div>
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Rollen', href: roles.index(wayfinderLocale()) },
    ],
};
