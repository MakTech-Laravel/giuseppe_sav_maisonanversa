import { Head, Link, WhenVisible, router } from '@inertiajs/react';
import {
    Calendar,
    Eye,
    Mail,
    Pencil,
    Plus,
    Search,
    Trash2,
    UserCheck,
    Users,
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
import customers from '@/routes/admin/customers';
import { avatarUrl } from '@/types/admin';
import type { AdminUser, Paginated } from '@/types/admin';
import { PERMISSIONS } from '@/types/permissions';

interface CustomersIndexProps {
    customers: Paginated<AdminUser>;
    filters: { search: string };
    stats?: { total: number; verified: number };
}

export default function CustomersIndex({
    customers: paginated,
    filters,
    stats,
}: CustomersIndexProps) {
    const { t } = useTranslation();
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
                customers.index(wayfinderLocale()).url,
                { search: search || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 350);

        return () => clearTimeout(timeout);
    }, [search]);

    return (
        <>
            <Head title={t('Klanten')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Klanten')}
                    description={t('Beheer ledenaccounts.')}
                    icon={Users}
                >
                    {can(PERMISSIONS.USERS.CREATE) && (
                        <Button asChild>
                            <Link href={customers.create(wayfinderLocale())}>
                                <Plus className="h-4 w-4" />{' '}
                                {t('Klant toevoegen')}
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
                                label={t('Totaal klanten')}
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
                        placeholder={t('Zoek klanten…')}
                        className="pl-9"
                    />
                </div>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50">
                                    <TableHead>{t('Klant')}</TableHead>
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
                                {paginated.data.map((customer) => {
                                    const url = avatarUrl(customer.avatar);

                                    return (
                                        <TableRow key={customer.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-secondary font-semibold">
                                                        {url ? (
                                                            <img
                                                                src={url}
                                                                alt={
                                                                    customer.name
                                                                }
                                                                className="h-full w-full object-cover"
                                                            />
                                                        ) : (
                                                            customer.name
                                                                .charAt(0)
                                                                .toUpperCase()
                                                        )}
                                                    </div>
                                                    <div className="min-w-0">
                                                        <span className="block truncate font-medium">
                                                            {customer.name}
                                                        </span>
                                                        <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                                            <Mail className="h-3 w-3" />
                                                            {customer.email}
                                                        </span>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden md:table-cell">
                                                {customer.email_verified_at
                                                    ? t('Geverifieerd')
                                                    : t('In afwachting')}
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <span className="flex items-center gap-1.5 text-sm text-muted-foreground">
                                                    <Calendar className="h-3.5 w-3.5" />
                                                    {new Date(
                                                        customer.created_at,
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
                                                                href={customers.show(
                                                                    {
                                                                        locale: wayfinderLocale(),
                                                                        user: customer.id,
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
                                                    ) && (
                                                        <Button
                                                            asChild
                                                            variant="ghost"
                                                            size="icon"
                                                        >
                                                            <Link
                                                                href={customers.edit(
                                                                    {
                                                                        locale: wayfinderLocale(),
                                                                        user: customer.id,
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
                                                    {can(
                                                        PERMISSIONS.USERS
                                                            .DELETE,
                                                    ) && (
                                                        <ConfirmDeleteDialog
                                                            description={t(
                                                                '{{name}} en alle bijbehorende gegevens permanent verwijderen?',
                                                                {
                                                                    name: customer.name,
                                                                },
                                                            )}
                                                            onConfirm={() =>
                                                                router.delete(
                                                                    customers.destroy(
                                                                        {
                                                                            locale: wayfinderLocale(),
                                                                            user: customer.id,
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
                                {t('Geen klanten gevonden.')}
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

CustomersIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Klanten', href: customers.index(wayfinderLocale()) },
    ],
};
