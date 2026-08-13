import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, UserPlus } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { UserForm } from '@/components/admin/user-form';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import customers from '@/routes/admin/customers';

export default function CreateCustomer() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Klant aanmaken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Klant aanmaken')}
                    description={t('Voeg een nieuw ledenaccount toe.')}
                    icon={UserPlus}
                >
                    <Button variant="outline" asChild>
                        <Link href={customers.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar klanten')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <div className="w-full rounded-xl border bg-card p-6 shadow-sm md:p-8">
                    <UserForm
                        action={customers.store(wayfinderLocale())}
                        roles={[]}
                        showRoles={false}
                        submitLabel={t('Klant aanmaken')}
                        onCancel={() =>
                            router.visit(customers.index(wayfinderLocale()))
                        }
                    />
                </div>
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
