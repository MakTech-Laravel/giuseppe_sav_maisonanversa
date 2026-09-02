import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ShieldPlus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { RoleForm } from '@/components/admin/role-form';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import roles from '@/routes/admin/roles';
import type { PermissionOption } from '@/types/admin';

export default function CreateRole({
    permissions,
}: {
    permissions: PermissionOption[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Rol aanmaken')} />

            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Rol aanmaken')}
                    description={t('Geef de rol een naam en ken rechten toe.')}
                    icon={ShieldPlus}
                >
                    <Button variant="outline" asChild>
                        <Link href={roles.index(wayfinderLocale()).url}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar rollen')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <RoleForm
                        action={roles.store(wayfinderLocale())}
                        permissions={permissions}
                        onCancel={() =>
                            router.visit(roles.index(wayfinderLocale()).url)
                        }
                    />
                </div>
            </div>
        </>
    );
}

CreateRole.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Rollen', href: roles.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: roles.create(wayfinderLocale()) },
    ],
};
