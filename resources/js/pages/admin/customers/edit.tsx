import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { UserForm, UserFormAside } from '@/components/admin/user-form';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import customers from '@/routes/admin/customers';
import type { AdminUser } from '@/types/admin';

export default function EditCustomer({ customer }: { customer: AdminUser }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Klant bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Klant bewerken')}
                    description={t('Werk het account van {{name}} bij.', {
                        name: customer.name,
                    })}
                    icon={Pencil}
                >
                    <Button variant="outline" asChild>
                        <Link href={customers.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar klanten')}
                        </Link>
                    </Button>
                    <Button variant="secondary" asChild>
                        <Link
                            href={customers.show({
                                locale: wayfinderLocale(),
                                user: customer.id,
                            })}
                        >
                            {t('Bekijk profiel')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <AdminResourceShell
                    aside={
                        <>
                            <UserFormAside
                                isEdit
                                entityLabel={customer.name}
                            />
                            <AdminPanel title={t('Account')}>
                                <dl className="space-y-2 text-sm">
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-muted-foreground">
                                            {t('Klant-ID')}
                                        </dt>
                                        <dd className="font-medium">
                                            #{customer.id}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-muted-foreground">
                                            {t('E-mail')}
                                        </dt>
                                        <dd className="truncate font-medium">
                                            {customer.email}
                                        </dd>
                                    </div>
                                </dl>
                            </AdminPanel>
                        </>
                    }
                >
                    <UserForm
                        action={customers.update({
                            locale: wayfinderLocale(),
                            user: customer.id,
                        })}
                        isEdit
                        currentAvatar={customer.avatar}
                        submitLabel={t('Klant opslaan')}
                        defaults={{
                            name: customer.name,
                            email: customer.email,
                        }}
                        onCancel={() =>
                            router.visit(customers.index(wayfinderLocale()))
                        }
                    />
                </AdminResourceShell>
            </div>
        </>
    );
}

EditCustomer.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Klanten', href: customers.index(wayfinderLocale()) },
        { title: 'Bewerken', href: customers.index(wayfinderLocale()) },
    ],
};
