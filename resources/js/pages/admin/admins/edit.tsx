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
import admins from '@/routes/admin/admins';
import type { AdminUser } from '@/types/admin';

export default function EditAdmin({
    user,
}: {
    user: AdminUser;
    isLastSuperAdmin?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Edit administrator')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Edit administrator')}
                    description={t('Update {{name}}\'s account.', {
                        name: user.name,
                    })}
                    icon={Pencil}
                >
                    <Button variant="outline" asChild>
                        <Link href={admins.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Back')}
                        </Link>
                    </Button>
                    <Button variant="secondary" asChild>
                        <Link
                            href={admins.show({
                                locale: wayfinderLocale(),
                                user: user.id,
                            })}
                        >
                            {t('View profile')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <AdminResourceShell
                    aside={
                        <>
                            <UserFormAside isEdit entityLabel={user.name} />
                            <AdminPanel title={t('Account')}>
                                <dl className="space-y-2 text-sm">
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-muted-foreground">
                                            {t('Administrator ID')}
                                        </dt>
                                        <dd className="font-medium">
                                            #{user.id}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-muted-foreground">
                                            {t('Email')}
                                        </dt>
                                        <dd className="truncate font-medium">
                                            {user.email}
                                        </dd>
                                    </div>
                                </dl>
                            </AdminPanel>
                            <AdminPanel title={t('Access')}>
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'Administrator accounts get full access to the admin panel.',
                                    )}
                                </p>
                            </AdminPanel>
                        </>
                    }
                >
                    <UserForm
                        action={admins.update({
                            locale: wayfinderLocale(),
                            user: user.id,
                        })}
                        isEdit
                        currentAvatar={user.avatar}
                        submitLabel={t('Save administrator')}
                        defaults={{
                            name: user.name,
                            email: user.email,
                        }}
                        onCancel={() =>
                            router.visit(admins.index(wayfinderLocale()))
                        }
                    />
                </AdminResourceShell>
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
