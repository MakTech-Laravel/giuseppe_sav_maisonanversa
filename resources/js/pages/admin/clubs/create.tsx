import { Head } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ClubForm } from '@/components/admin/club-form';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import clubsRoutes from '@/routes/admin/clubs';
import type { ClubFormOptions } from '@/types/club';

export default function ClubCreate({ options }: { options: ClubFormOptions }) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    return (
        <>
            <Head title={t('Club toevoegen')} />
            <div className="w-full max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Club toevoegen')}
                    description={t(
                        'Door de Maison aangemaakte clubs zijn direct beschikbaar voor leden.',
                    )}
                    icon={Building2}
                />

                <ClubForm
                    options={options}
                    action={clubsRoutes.store(locale).url}
                    method="post"
                    submitLabel={t('Club toevoegen')}
                />
            </div>
        </>
    );
}

ClubCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Clubs', href: clubsRoutes.index(wayfinderLocale()) },
        { title: 'Toevoegen', href: clubsRoutes.create(wayfinderLocale()) },
    ],
};
