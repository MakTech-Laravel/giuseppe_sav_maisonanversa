import { Head, Link, router } from '@inertiajs/react';
import { BookText, Eye, UsersRound } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { translateMemberStatus } from '@/lib/circle-member-status';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import circleRoutes from '@/routes/admin/circle';
import customers from '@/routes/admin/customers';

interface CircleMember {
    id: string;
    edition: string | null;
    name: string;
    email: string;
    status: string;
    joined_at: string | null;
}

export default function CircleIndex({ members }: { members: CircleMember[] }) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    function removeMember(memberId: string) {
        if (!window.confirm(t('Lid verwijderen uit Founding Circle?'))) {
            return;
        }

        router.delete(
            circleRoutes.remove({
                locale,
                member: Number(memberId),
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
                        'Bekijk Founding Edition-leden. Wijs plaatsen toe via het Naamregister — elke rij is al een editienummer.',
                    )}
                    icon={UsersRound}
                >
                    <Button asChild>
                        <Link href={circleRoutes.register(locale)}>
                            <BookText className="h-4 w-4" /> {t('Naamregister')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead>{t('Editie')}</TableHead>
                                <TableHead>{t('Lid')}</TableHead>
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
                            {members.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-10 text-center text-sm text-muted-foreground"
                                    >
                                        {t(
                                            'Nog geen leden. Open het Naamregister om een klant op een plaats te zetten.',
                                        )}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                members.map((member) => (
                                    <TableRow key={member.id}>
                                        <TableCell className="font-medium">
                                            {member.edition
                                                ? `№ ${member.edition}`
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Link
                                                href={customers.show({
                                                    locale,
                                                    user: Number(member.id),
                                                })}
                                                className="block min-w-0"
                                            >
                                                <span className="font-medium text-foreground underline-offset-2 hover:underline">
                                                    {member.name}
                                                </span>
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {member.email}
                                                </p>
                                            </Link>
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
                                                        locale,
                                                        member: Number(
                                                            member.id,
                                                        ),
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
                                ))
                            )}
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
