import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ShieldCheck } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { RoleForm } from '@/components/admin/role-form';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import roles from '@/routes/admin/roles';
import type { AdminRoleDetail, PermissionOption } from '@/types/admin';

interface EditRoleProps {
    role: AdminRoleDetail;
    permissions: PermissionOption[];
}

export default function EditRole({ role, permissions }: EditRoleProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Rol bewerken')} />

            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Rol bewerken')}
                    description={t(
                        'Werk de rol {{name}} en de bijbehorende rechten bij.',
                        { name: role.name },
                    )}
                    icon={ShieldCheck}
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
                        action={roles.update({
                            locale: wayfinderLocale(),
                            role: role.id,
                        })}
                        permissions={permissions}
                        isEdit
                        locked={role.is_super_admin}
                        defaults={{
                            name: role.name,
                            permissions: role.permissions,
                        }}
                        onCancel={() =>
                            router.visit(roles.index(wayfinderLocale()).url)
                        }
                    />
                </div>
            </div>
        </>
    );
}

EditRole.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Rollen', href: roles.index(wayfinderLocale()) },
        { title: 'Bewerken', href: roles.index(wayfinderLocale()) },
    ],
};
