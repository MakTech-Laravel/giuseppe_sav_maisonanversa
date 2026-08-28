import { Head } from '@inertiajs/react';
import { CalendarCheck } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { InquiryShow } from '@/components/admin/inquiry-show';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import appointments from '@/routes/admin/appointments';
import type { InquiryDetails, InquiryRouteHelpers } from '@/types/inquiry';

export default function AppointmentsShow({
    inquiry,
}: {
    inquiry: InquiryDetails;
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={inquiry.name} />
            <InquiryShow
                title={inquiry.name}
                description={t('Bekijk deze afspraakaanvraag.')}
                icon={CalendarCheck}
                inquiry={inquiry}
                routes={appointments as InquiryRouteHelpers}
            />
        </>
    );
}

AppointmentsShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Afspraken', href: appointments.index(wayfinderLocale()) },
        { title: 'Gegevens', href: '#' },
    ],
};
