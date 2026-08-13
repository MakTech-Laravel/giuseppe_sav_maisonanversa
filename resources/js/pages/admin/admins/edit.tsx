import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { UserForm } from '@/components/admin/user-form';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import admins from '@/routes/admin/admins';
import type { AdminUser } from '@/types/admin';

export default function EditAdmin({
    user,
    isLastSuperAdmin = false,
}: {
    user: AdminUser;
    isLastSuperAdmin?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Beheerder bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Beheerder bewerken')}
                    description={t('Werk het account van {{name}} bij.', {
                        name: user.name,
                    })}
                    icon={Pencil}
                >
                    <Button variant="outline" asChild>
                        <Link href={admins.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="w-full rounded-xl border bg-card p-6 shadow-sm md:p-8">
                    <UserForm
                        action={admins.update({
                            locale: wayfinderLocale(),
                            user: user.id,
                        })}
                        roles={[]}
                        showRoles={false}
                        isEdit
                        currentAvatar={user.avatar}
                        isLastSuperAdmin={isLastSuperAdmin}
                        submitLabel={t('Beheerder opslaan')}
                        defaults={{
                            name: user.name,
                            email: user.email,
                            roles: [],
                        }}
                        onCancel={() =>
                            router.visit(admins.index(wayfinderLocale()))
                        }
                    />
                </div>
            </div>
        </>
    );
}

EditAdmin.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Beheerder', href: admins.index(wayfinderLocale()) },
        { title: 'Bewerken', href: admins.index(wayfinderLocale()) },
    ],
};
