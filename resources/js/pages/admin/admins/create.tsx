import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, UserPlus } from 'lucide-react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { UserForm } from '@/components/admin/user-form';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import admins from '@/routes/admin/admins';

export default function CreateAdmin() {
    return (
        <>
            <Head title="Create administrator" />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title="Create administrator"
                    description="Add a staff administrator account."
                    icon={UserPlus}
                >
                    <Button variant="outline" asChild>
                        <Link href={admins.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> Back
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="max-w-3xl rounded-xl border bg-card p-6 shadow-sm">
                    <UserForm
                        action={admins.store(wayfinderLocale())}
                        roles={[]}
                        showRoles={false}
                        submitLabel="Create administrator"
                        onCancel={() =>
                            router.visit(admins.index(wayfinderLocale()))
                        }
                    />
                </div>
            </div>
        </>
    );
}

CreateAdmin.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Administrator', href: admins.index(wayfinderLocale()) },
        { title: 'Create', href: admins.create(wayfinderLocale()) },
    ],
};
