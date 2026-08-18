import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, UserPlus } from 'lucide-react';
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

export default function CreateAdmin() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Beheerder aanmaken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Beheerder aanmaken')}
                    description={t('Voeg een personeelsaccount toe.')}
                    icon={UserPlus}
                >
                    <Button variant="outline" asChild>
                        <Link href={admins.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <AdminResourceShell
                    aside={
                        <>
                            <UserFormAside entityLabel={t('Beheerder')} />
                            <AdminPanel title={t('Toegang')}>
                                <p className="text-sm text-muted-foreground">
                                    {t(
                                        'Beheerderaccounts krijgen volledige toegang tot het adminpaneel.',
                                    )}
                                </p>
                            </AdminPanel>
                        </>
                    }
                >
                    <UserForm
                        action={admins.store(wayfinderLocale())}
                        submitLabel={t('Beheerder aanmaken')}
                        onCancel={() =>
                            router.visit(admins.index(wayfinderLocale()))
                        }
                    />
                </AdminResourceShell>
            </div>
        </>
    );
}

CreateAdmin.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Beheerder', href: admins.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: admins.create(wayfinderLocale()) },
    ],
};
