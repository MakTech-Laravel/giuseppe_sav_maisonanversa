import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    IdCard,
    Medal,
    Sparkles,
    UsersRound,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { translateMemberStatus } from '@/lib/circle-member-status';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import circleRoutes from '@/routes/admin/circle';
import customers from '@/routes/admin/customers';

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
    const locale = wayfinderLocale();
    const customerHref = customers.show({
        locale,
        user: Number(member.id),
    });

    function removeMember() {
        if (!window.confirm(t('Lid verwijderen uit Founding Circle?'))) {
            return;
        }

        router.delete(
            circleRoutes.remove({
                locale,
                member: Number(member.id),
            }).url,
            {
                onSuccess: () => {
                    router.visit(circleRoutes.index(locale).url);
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
                    title={member.name}
                    description={t(
                        'Founding Circle-plaats en voordelen voor dit lid.',
                    )}
                    icon={UsersRound}
                >
                    <Button variant="outline" asChild>
                        <Link href={circleRoutes.index(locale)}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar Founding Circle')}
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={customerHref}>
                            <IdCard className="h-4 w-4" />{' '}
                            {t('Klantenprofiel')}
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

                <AdminResourceShell
                    aside={
                        <AdminPanel
                            title={t('Acties')}
                            description={t(
                                'Open het klantaccount of verwijder dit lid uit de Circle.',
                            )}
                        >
                            <div className="flex flex-col gap-2">
                                <Button asChild className="w-full">
                                    <Link href={customerHref}>
                                        <IdCard className="h-4 w-4" />{' '}
                                        {t('Klantenprofiel')}
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    asChild
                                    className="w-full"
                                >
                                    <Link href={circleRoutes.index(locale)}>
                                        <ArrowLeft className="h-4 w-4" />{' '}
                                        {t('Terug naar Founding Circle')}
                                    </Link>
                                </Button>
                                <Button
                                    variant="destructive"
                                    type="button"
                                    className="w-full"
                                    onClick={removeMember}
                                >
                                    {t('Verwijderen')}
                                </Button>
                            </div>
                        </AdminPanel>
                    }
                >
                    <div className="space-y-6">
                        <AdminPanel>
                            <div className="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-center gap-4">
                                    <div className="flex size-16 items-center justify-center rounded-xl border border-border bg-muted/40 font-serif text-2xl tracking-wide">
                                        {member.edition
                                            ? `№ ${member.edition}`
                                            : '—'}
                                    </div>
                                    <div className="min-w-0">
                                        <p className="text-xs font-medium tracking-[0.18em] text-muted-foreground uppercase">
                                            {t('Editieplaats')}
                                        </p>
                                        <h2 className="mt-1 font-serif text-2xl leading-none">
                                            {member.edition
                                                ? `${t('Editie')} № ${member.edition}`
                                                : t('Nog geen editie toegewezen')}
                                        </h2>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {t(
                                                'Eén vaste plaats in Heritage No.001 (1–100).',
                                            )}
                                        </p>
                                    </div>
                                </div>
                                <Badge variant="secondary" className="w-fit">
                                    {translateMemberStatus(member.status, t)}
                                </Badge>
                            </div>
                        </AdminPanel>

                        <AdminPanel
                            title={t('Lid')}
                            description={t(
                                'Accountgegevens. Klik door naar het volledige klantenprofiel.',
                            )}
                        >
                            <dl className="grid gap-5 sm:grid-cols-2">
                                <div className="space-y-1">
                                    <dt className="text-xs text-muted-foreground">
                                        {t('Naam')}
                                    </dt>
                                    <dd>
                                        <Link
                                            href={customerHref}
                                            className="font-medium underline-offset-2 hover:underline"
                                        >
                                            {member.name}
                                        </Link>
                                    </dd>
                                </div>
                                <div className="space-y-1">
                                    <dt className="text-xs text-muted-foreground">
                                        {t('E-mail')}
                                    </dt>
                                    <dd>
                                        <Link
                                            href={customerHref}
                                            className="font-medium underline-offset-2 hover:underline"
                                        >
                                            {member.email}
                                        </Link>
                                    </dd>
                                </div>
                                <div className="space-y-1">
                                    <dt className="text-xs text-muted-foreground">
                                        {t('Ingeschreven op')}
                                    </dt>
                                    <dd className="font-medium">
                                        {member.joined_at ?? '—'}
                                    </dd>
                                </div>
                                <div className="space-y-1">
                                    <dt className="text-xs text-muted-foreground">
                                        {t('Klant-ID')}
                                    </dt>
                                    <dd className="font-medium tabular-nums">
                                        {member.id}
                                    </dd>
                                </div>
                            </dl>
                        </AdminPanel>

                        <AdminPanel
                            title={t('Voordelen')}
                            description={t(
                                'Wat dit Founding Circle-lid ontvangt.',
                            )}
                        >
                            <ul className="grid gap-3 sm:grid-cols-2">
                                {member.benefits.map((benefit, index) => (
                                    <li
                                        key={benefit}
                                        className="flex items-start gap-3 rounded-lg border bg-muted/20 px-4 py-3 text-sm"
                                    >
                                        {index === 0 ? (
                                            <Medal className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                        ) : (
                                            <Sparkles className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                        )}
                                        <span>{benefit}</span>
                                    </li>
                                ))}
                            </ul>
                        </AdminPanel>
                    </div>
                </AdminResourceShell>
            </div>
        </>
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
