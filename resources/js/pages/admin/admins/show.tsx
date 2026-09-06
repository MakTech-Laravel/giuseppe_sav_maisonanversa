import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, IdCard, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { AdminUserProfile } from '@/components/admin/admin-user-profile';
import { Button } from '@/components/ui/button';
import { usePermission } from '@/hooks/use-permissions';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import admins from '@/routes/admin/admins';
import type { AdminUser } from '@/types/admin';
import { PERMISSIONS } from '@/types/permissions';

export default function ShowAdmin({ user }: { user: AdminUser }) {
    const { t } = useTranslation();
    const { can } = usePermission();

    return (
        <>
            <Head title={user.name} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader title={t('Beheerdersprofiel')} icon={IdCard}>
                    <Button variant="outline" asChild>
                        <Link href={admins.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    {can(PERMISSIONS.USERS.EDIT) && (
                        <Button asChild>
                            <Link
                                href={admins.edit({
                                    locale: wayfinderLocale(),
                                    user: user.id,
                                })}
                            >
                                <Pencil className="h-4 w-4" /> {t('Bewerken')}
                            </Link>
                        </Button>
                    )}
                </AdminPageHeader>

                <AdminResourceShell
                    aside={
                        <AdminPanel
                            title={t('Acties')}
                            description={t(
                                'Beheer dit personeelsaccount vanuit het adminpaneel.',
                            )}
                        >
                            <div className="flex flex-col gap-2">
                                {can(PERMISSIONS.USERS.EDIT) && (
                                    <Button asChild className="w-full">
                                        <Link
                                            href={admins.edit({
                                                locale: wayfinderLocale(),
                                                user: user.id,
                                            })}
                                        >
                                            <Pencil className="h-4 w-4" />{' '}
                                            {t('Bewerken')}
                                        </Link>
                                    </Button>
                                )}
                                <Button
                                    variant="outline"
                                    asChild
                                    className="w-full"
                                >
                                    <Link
                                        href={admins.index(wayfinderLocale())}
                                    >
                                        <ArrowLeft className="h-4 w-4" />{' '}
                                        {t('Terug naar beheerders')}
                                    </Link>
                                </Button>
                            </div>
                        </AdminPanel>
                    }
                >
                    <AdminUserProfile user={user} idLabel={t('Beheerder-ID')} />
                </AdminResourceShell>
            </div>
        </>
    );
}

ShowAdmin.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Beheerder', href: admins.index(wayfinderLocale()) },
        { title: 'Profiel', href: admins.index(wayfinderLocale()) },
    ],
};
