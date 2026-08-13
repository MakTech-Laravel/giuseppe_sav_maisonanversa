import { Head, Link } from '@inertiajs/react';
import { Eye, TriangleAlert, UsersRound } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import circleRoutes from '@/routes/admin/circle';

interface CircleMember {
    id: string;
    edition: string;
    name: string;
    email: string;
    status: string;
    status_key: string;
    joined_at: string;
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
    circleConnected,
}: {
    members: CircleMember[];
    circleConnected: boolean;
}) {
    const { t } = useTranslation();

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
                {!circleConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>
                            {t('Circle-ledenbestand is niet gekoppeld')}
                        </AlertTitle>
                        <AlertDescription>
                            {t(
                                'Deze leden zijn demogegevens totdat betaalde edities aan het roster zijn gekoppeld.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}
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
                                        № {member.edition}
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
                                        {member.joined_at}
                                    </TableCell>
                                    <TableCell className="text-right">
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
