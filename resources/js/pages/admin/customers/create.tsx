import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, UserPlus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { AdminResourceShell } from '@/components/admin/admin-resource-shell';
import { UserForm, UserFormAside } from '@/components/admin/user-form';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import customers from '@/routes/admin/customers';

export default function CreateCustomer() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Create customer')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Create customer')}
                    description={t('Add a new member account.')}
                    icon={UserPlus}
                >
                    <Button variant="outline" asChild>
                        <Link href={customers.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Back to customers')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <AdminResourceShell
                    aside={<UserFormAside entityLabel={t('Customer')} />}
                >
                    <UserForm
                        action={customers.store(wayfinderLocale())}
                        submitLabel={t('Create customer')}
                        onCancel={() =>
                            router.visit(customers.index(wayfinderLocale()))
                        }
                    />
                </AdminResourceShell>
            </div>
        </>
    );
}

CreateCustomer.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Klanten', href: customers.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: customers.create(wayfinderLocale()) },
    ],
};
