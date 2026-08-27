import { Head } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { PartnerClubForm } from '@/components/admin/partner-club-form';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import partnerClubsRoutes from '@/routes/admin/partner-clubs';

export default function PartnerClubCreate() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Club toevoegen')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Club toevoegen')}
                    description={t('Voeg een partnerclub toe aan Club Corner.')}
                    icon={MapPin}
                />

                <PartnerClubForm
                    action={partnerClubsRoutes.store(wayfinderLocale()).url}
                    method="post"
                    submitLabel={t('Club toevoegen')}
                />
            </div>
        </>
    );
}

PartnerClubCreate.layout = {
    title: 'Partner Clubs',
};
