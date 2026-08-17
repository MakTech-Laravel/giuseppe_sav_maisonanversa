import { Head, Link, router, useForm } from '@inertiajs/react';
import { Eye, UsersRound } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
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
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import circleRoutes from '@/routes/admin/circle';

interface CircleMember {
    id: string;
    edition: string | null;
    name: string;
    email: string;
    status: string;
    joined_at: string | null;
}

function translateMemberStatus(
    status: string,
    t: (key: string) => string,
): string {
    const statusMap: Record<string, string> = {
        Active: 'Actief',
        Reserved: 'Gereserveerd',
    };

    return t(statusMap[status] ?? status);
}

export default function CircleIndex({
    members,
}: {
    members: CircleMember[];
}) {
    const { t } = useTranslation();
    const form = useForm(circleRoutes.assign(wayfinderLocale()), {
        email: '',
        user_id: '',
    });

    function submitAssign(event: FormEvent) {
        event.preventDefault();
        form
            .transform((data) => ({
                email: data.email || null,
                user_id: data.user_id === '' ? null : Number(data.user_id),
            }))
            .submit({
                onSuccess: () => form.reset(),
            });
    }

    function removeMember(memberId: string) {
        if (!window.confirm(t('Lid verwijderen uit Founding Circle?'))) {
            return;
        }

        router.delete(
            circleRoutes.remove({
                locale: wayfinderLocale(),
                member: memberId,
            }).url,
        );
    }

    return (
        <>
            <Head title={t('Founding Circle')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Founding Circle')}
                    description={t(
                        'Bekijk Founding Edition-leden en reserveringen (1–100).',
                    )}
                    icon={UsersRound}
                />
                <form
                    onSubmit={submitAssign}
                    className="flex flex-col gap-3 rounded-xl border bg-card p-5 shadow-sm sm:flex-row sm:items-end"
                >
                    <div className="flex-1 space-y-1">
                        <p className="text-sm font-medium">
                            {t('Lid toevoegen')}
                        </p>
                        <Input
                            type="email"
                            placeholder={t('E-mail')}
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                        />
                        {form.errors.email && (
                            <p className="text-sm text-destructive">
                                {form.errors.email}
                            </p>
                        )}
                    </div>
                    <div className="w-full space-y-1 sm:w-40">
                        <Input
                            type="number"
                            placeholder={t('User ID')}
                            value={form.data.user_id}
                            onChange={(event) =>
                                form.setData('user_id', event.target.value)
                            }
                        />
                        {form.errors.user_id && (
                            <p className="text-sm text-destructive">
                                {form.errors.user_id}
                            </p>
                        )}
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        {t('Toewijzen')}
                    </Button>
                </form>
                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Editie')}</TableHead>
                                <TableHead>{t('Lid')}</TableHead>
                                <TableHead className="hidden md:table-cell">
                                    {t('E-mail')}
                                </TableHead>
                                <TableHead>{t('Status')}</TableHead>
                                <TableHead className="hidden sm:table-cell">
                                    {t('Ingeschreven op')}
                                </TableHead>
                                <TableHead className="text-right">
                                    {t('Acties')}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {members.map((member) => (
                                <TableRow key={member.id}>
                                    <TableCell className="font-medium">
                                        {member.edition
                                            ? `№ ${member.edition}`
                                            : '—'}
                                    </TableCell>
                                    <TableCell>{member.name}</TableCell>
                                    <TableCell className="hidden md:table-cell">
                                        {member.email}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {translateMemberStatus(
                                                member.status,
                                                t,
                                            )}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="hidden sm:table-cell">
                                        {member.joined_at ?? '—'}
                                    </TableCell>
                                    <TableCell className="space-x-1 text-right">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            asChild
                                        >
                                            <Link
                                                href={circleRoutes.show({
                                                    locale: wayfinderLocale(),
                                                    member: member.id,
                                                })}
                                                title={t('Lid bekijken')}
                                            >
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            type="button"
                                            onClick={() =>
                                                removeMember(member.id)
                                            }
                                        >
                                            {t('Verwijderen')}
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

CircleIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Founding Circle',
            href: circleRoutes.index(wayfinderLocale()),
        },
    ],
};
