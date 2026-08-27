import { Head } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ClubForm } from '@/components/admin/club-form';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import clubsRoutes from '@/routes/admin/clubs';
import type { AdminClubFormValues, ClubFormOptions } from '@/types/club';

export default function ClubEdit({
    club,
    options,
}: {
    club: AdminClubFormValues;
    options: ClubFormOptions;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    return (
        <>
            <Head title={club.name} />
            <div className="w-full max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={club.name}
                    description={t('Clubgegevens bijwerken.')}
                    icon={Building2}
                />

                <ClubForm
                    options={options}
                    club={club}
                    action={clubsRoutes.update({ locale, club: club.id }).url}
                    method="put"
                    submitLabel={t('Opslaan')}
                />
            </div>
        </>
    );
}

ClubEdit.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Clubs', href: clubsRoutes.index(wayfinderLocale()) },
    ],
};
