import { Head, Link, WhenVisible, router, usePage } from '@inertiajs/react';
import {
    Calendar,
    Eye,
    Mail,
    Pencil,
    Plus,
    Search,
    Trash2,
    UserCheck,
    UserRoundCog,
    UsersRound,
} from 'lucide-react';
import { motion } from 'motion/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { DataPagination } from '@/components/admin/data-pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    filters: { search: string };
    superAdminCount: number;
    stats?: { total: number; verified: number };
}

export default function AdminsIndex({
    users: paginated,
    filters,
    superAdminCount,
    stats,
}: AdminsIndexProps) {
    const { t } = useTranslation();
    const { can } = usePermission();
    const actorIsSuperAdmin =
        usePage().props.auth.user?.is_super_admin ?? false;
    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = setTimeout(() => {
            router.get(
                admins.index(wayfinderLocale()).url,
                { search: search || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search]);

    return (
        <>
            <Head title={t('Beheerder')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Beheerder')}
                    description={t('Beheer personeelsaccounts.')}
                    icon={UserRoundCog}
                >
                    {can(PERMISSIONS.USERS.CREATE) && (
                        <Button asChild>
                            <Link href={admins.create(wayfinderLocale())}>
                                <Plus className="h-4 w-4" />{' '}
                                {t('Beheerder toevoegen')}
                            </Link>
                        </Button>
                    )}
                </AdminPageHeader>

                <WhenVisible
                    data="stats"
                    fallback={
                        <div className="grid gap-4 sm:grid-cols-2">
                            {[0, 1].map((item) => (
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
                            className="grid gap-4 sm:grid-cols-2"
                        >
                            <StatCard
                                label={t('Totaal beheerders')}
                                value={stats.total}
                                icon={UsersRound}
                            />
                            <StatCard
                                label={t('Geverifieerd')}
                                value={stats.verified}
                                icon={UserCheck}
                            />
                        </motion.div>
                    )}
                </WhenVisible>

                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={t('Zoek beheerders…')}
                        className="pl-9"
                    />
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50">
                                    <TableHead>{t('Beheerder')}</TableHead>
                                    <TableHead className="hidden md:table-cell">
                                        {t('Status')}
                                    </TableHead>
                                    <TableHead className="hidden lg:table-cell">
                                        {t('Lid sinds')}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {t('Acties')}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {paginated.data.map((user) => {
                                    const url = avatarUrl(user.avatar);
                                    const isSuperAdmin = user.roles?.some(
                                        (role) => role.name === SUPER_ADMIN_ROLE,
                                    );
                                    const isLastSuperAdmin =
                                        isSuperAdmin && superAdminCount <= 1;
                                    const canDelete =
                                        can(PERMISSIONS.USERS.DELETE) &&
                                        (!isSuperAdmin || actorIsSuperAdmin) &&
                                        !isLastSuperAdmin;

                                    return (
                                        <TableRow key={user.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-secondary font-semibold">
                                                        {url ? (
                                                            <img
                                                                src={url}
                                                                alt={user.name}
                                                                className="h-full w-full object-cover"
                                                            />
                                                        ) : (
                                                            user.name
                                                                .charAt(0)
                                                                .toUpperCase()
                                                        )}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <span className="block truncate font-medium">
                                                            {user.name}
                                                        </span>
                                                        <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                                            <Mail className="h-3 w-3" />
                                                            {user.email}
                                                        </span>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                {user.email_verified_at
                                                    ? t('Geverifieerd')
                                                    : t('In afwachting')}
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                                    <Calendar className="h-3.5 w-3.5" />
                                                    {new Date(
                                                        user.created_at,
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
                                                                        user: user.id,
                                                                    },
                                                                )}
                                                                title={t(
                                                                    'Bekijken',
                                                                )}
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                                    {can(
                                                        PERMISSIONS.USERS.EDIT,
                                                    ) &&
                                                        (!isSuperAdmin ||
                                                            actorIsSuperAdmin) && (
                                                            <Button
                                                                asChild
                                                                variant="ghost"
                                                                size="icon"
                                                            >
                                                                <Link
                                                                    href={admins.edit(
                                                                        {
                                                                            locale: wayfinderLocale(),
                                                                            user: user.id,
                                                                        },
                                                                    )}
                                                                    title={t(
                                                                        'Bewerken',
                                                                    )}
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                        )}
                                                    {canDelete && (
                                                        <ConfirmDeleteDialog
                                                            description={t(
                                                                '{{name}} permanent verwijderen?',
                                                                {
                                                                    name: user.name,
                                                                },
                                                            )}
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    admins.destroy(
                                                                        {
                                                                            locale: wayfinderLocale(),
                                                                            user: user.id,
                                                                        },
                                                                    ).url,
                                                                )
                                                            }
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="text-muted-foreground hover:text-destructive"
                                                                title={t(
                                                                    'Verwijderen',
                                                                )}
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
                                {t('Geen beheerders gevonden.')}
                            </div>
                        )}
                    </div>
                    <DataPagination meta={paginated} />
                </div>
            </div>
        </>
    );
}

function StatCard({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value: number;
    icon: typeof UserRoundCog;
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
        { title: 'Beheerder', href: admins.index(wayfinderLocale()) },
    ],
};
