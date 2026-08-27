import { Head } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { PartnerClubForm } from '@/components/admin/partner-club-form';
import type { PartnerClubValues } from '@/components/admin/partner-club-form';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import partnerClubsRoutes from '@/routes/admin/partner-clubs';

export default function PartnerClubEdit({ club }: { club: PartnerClubValues }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={club.city} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={club.city}
                    description={t('Partnerclub bijwerken.')}
                    icon={MapPin}
                />

                <PartnerClubForm
                    club={club}
                    action={
                        partnerClubsRoutes.update({
                            locale: wayfinderLocale(),
                            partnerClub: Number(club.id),
                        }).url
                    }
                    method="put"
                    submitLabel={t('Opslaan')}
                />
            </div>
        </>
    );
}

PartnerClubEdit.layout = {
    title: 'Partner Clubs',
};
