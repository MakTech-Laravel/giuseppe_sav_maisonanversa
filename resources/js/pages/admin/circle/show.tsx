import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, UsersRound } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { translateMemberStatus } from '@/lib/circle-member-status';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import circleRoutes from '@/routes/admin/circle';

interface CircleMemberDetail {
    id: string;
    edition: string | null;
    name: string;
    email: string;
    status: string;
    joined_at: string | null;
    benefits: string[];
}

export default function CircleShow({ member }: { member: CircleMemberDetail }) {
    const { t } = useTranslation();

    function removeMember() {
        if (!window.confirm(t('Lid verwijderen uit Founding Circle?'))) {
            return;
        }

        router.delete(
            circleRoutes.remove({
                locale: wayfinderLocale(),
                member: Number(member.id),
            }).url,
            {
                onSuccess: () => {
                    router.visit(circleRoutes.index(wayfinderLocale()).url);
                },
            },
        );
    }

    return (
        <>
            <Head
                title={
                    member.edition
                        ? `${t('Editie')} № ${member.edition}`
                        : member.name
                }
            />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={
                        member.edition
                            ? `${t('Editie')} № ${member.edition}`
                            : member.name
                    }
                    description={member.email}
                    icon={UsersRound}
                >
                    <Button variant="outline" asChild>
                        <Link href={circleRoutes.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar Founding Circle')}
                        </Link>
                    </Button>
                    <Button
                        variant="destructive"
                        type="button"
                        onClick={removeMember}
                    >
                        {t('Verwijderen')}
                    </Button>
                </AdminPageHeader>
                <div className="grid w-full gap-6 lg:grid-cols-3">
                    <dl className="space-y-4 rounded-xl border bg-card p-6 text-sm shadow-sm">
                        <Detail label={t('Referentie')} value={member.id} />
                        <Detail label={t('Naam')} value={member.name} />
                        <Detail label={t('E-mail')} value={member.email} />
                        <Detail
                            label={t('Ingeschreven op')}
                            value={member.joined_at ?? '—'}
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
