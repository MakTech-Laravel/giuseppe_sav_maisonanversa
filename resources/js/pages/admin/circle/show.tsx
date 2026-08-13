import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, TriangleAlert, UsersRound } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import circleRoutes from '@/routes/admin/circle';

interface CircleMemberDetail {
    id: string;
    edition: string;
    name: string;
    email: string;
    status: string;
    joined_at: string;
    summary: string;
    benefits: string[];
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

export default function CircleShow({
    member,
    circleConnected,
}: {
    member: CircleMemberDetail;
    circleConnected: boolean;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={`${t('Editie')} № ${member.edition}`} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={`${t('Editie')} № ${member.edition}`}
                    description={member.summary}
                    icon={UsersRound}
                >
                    <Button variant="outline" asChild>
                        <Link href={circleRoutes.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar Founding Circle')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                {!circleConnected && (
                    <Alert>
                        <TriangleAlert className="h-4 w-4" />
                        <AlertTitle>{t('Demo-lid')}</AlertTitle>
                        <AlertDescription>
                            {t(
                                'Live lidmaatschapsgegevens verschijnen hier nadat edities aan betaalde bestellingen zijn gekoppeld.',
                            )}
                        </AlertDescription>
                    </Alert>
                )}
                <div className="grid max-w-4xl gap-6 lg:grid-cols-3">
                    <dl className="space-y-4 rounded-xl border bg-card p-6 text-sm shadow-sm">
                        <Detail label={t('Referentie')} value={member.id} />
                        <Detail label={t('Naam')} value={member.name} />
                        <Detail label={t('E-mail')} value={member.email} />
                        <Detail
                            label={t('Ingeschreven op')}
                            value={member.joined_at}
                        />
                        <div>
                            <dt className="text-muted-foreground">
                                {t('Status')}
                            </dt>
                            <dd className="mt-1">
                                <Badge variant="secondary">
                                    {translateMemberStatus(member.status, t)}
                                </Badge>
                            </dd>
                        </div>
                    </dl>
                    <div className="rounded-xl border bg-card p-6 shadow-sm lg:col-span-2">
                        <h2 className="mb-4 text-sm font-semibold">
                            {t('Voordelen')}
                        </h2>
                        <ul className="list-inside list-disc space-y-2 text-sm text-muted-foreground">
                            {member.benefits.map((benefit) => (
                                <li key={benefit}>{benefit}</li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="mt-1 font-medium">{value}</dd>
        </div>
    );
}

CircleShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Founding Circle',
            href: circleRoutes.index(wayfinderLocale()),
        },
        { title: 'Gegevens', href: circleRoutes.index(wayfinderLocale()) },
    ],
};
