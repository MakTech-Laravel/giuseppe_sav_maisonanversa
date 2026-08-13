import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { UserForm } from '@/components/admin/user-form';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import admins from '@/routes/admin/admins';
import type { AdminUser, RoleRef } from '@/types/admin';

interface EditAdminProps {
    user: AdminUser;
    roles: RoleRef[];
    userRoles: string[];
    isLastSuperAdmin: boolean;
}

export default function EditAdmin({
    user,
    roles,
    userRoles,
    isLastSuperAdmin,
}: EditAdminProps) {
    return (
        <>
            <Head title={`Edit ${user.name}`} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title="Edit admin"
                    description={`Update ${user.name}'s account and roles.`}
                    icon={Pencil}
                >
                    <Button variant="outline" asChild>
                        <Link href={admins.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> Back to admins
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="max-w-3xl rounded-xl border bg-card p-6 shadow-sm">
                    <UserForm
                        action={admins.update({
                            locale: wayfinderLocale(),
                            user: user.id,
                        })}
                        roles={roles}
                        isEdit
                        currentAvatar={user.avatar}
                        isLastSuperAdmin={isLastSuperAdmin}
                        submitLabel="Save admin"
                        defaults={{
                            name: user.name,
                            email: user.email,
                            roles: userRoles,
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
        { title: 'Admins', href: admins.index(wayfinderLocale()) },
        { title: 'Edit', href: admins.index(wayfinderLocale()) },
    ],
};
