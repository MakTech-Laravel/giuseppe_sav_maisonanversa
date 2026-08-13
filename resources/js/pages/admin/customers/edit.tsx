import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { UserForm } from '@/components/admin/user-form';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import customers from '@/routes/admin/customers';
import type { AdminUser } from '@/types/admin';

export default function EditCustomer({ customer }: { customer: AdminUser }) {
    return (
        <>
            <Head title={`Edit ${customer.name}`} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title="Edit customer"
                    description={`Update ${customer.name}'s account.`}
                    icon={Pencil}
                >
                    <Button variant="outline" asChild>
                        <Link href={customers.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> Back to customers
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="w-full rounded-xl border bg-card p-6 shadow-sm md:p-8">
                    <UserForm
                        action={customers.update({
                            locale: wayfinderLocale(),
                            user: customer.id,
                        })}
                        roles={[]}
                        showRoles={false}
                        isEdit
                        currentAvatar={customer.avatar}
                        submitLabel="Save customer"
                        defaults={{
                            name: customer.name,
                            email: customer.email,
                            roles: [],
                        }}
                        onCancel={() =>
                            router.visit(customers.index(wayfinderLocale()))
                        }
                    />
                </div>
            </div>
        </>
    );
}

EditCustomer.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Customers', href: customers.index(wayfinderLocale()) },
        { title: 'Edit', href: customers.index(wayfinderLocale()) },
    ],
};
