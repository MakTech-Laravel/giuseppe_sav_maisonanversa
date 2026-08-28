import { Head } from '@inertiajs/react';
import { CalendarCheck } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { InquiryInbox } from '@/components/admin/inquiry-inbox';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import appointments from '@/routes/admin/appointments';
import type { Paginated } from '@/types/admin';
import type {
    InquiryFilters,
    InquiryKindOption,
    InquiryListItem,
    InquiryRouteHelpers,
} from '@/types/inquiry';

export default function AppointmentsIndex({
    inquiries,
    filters,
    perPageOptions = [10, 15, 25, 50, 100],
    kinds = [],
}: {
    inquiries: Paginated<InquiryListItem>;
    filters: InquiryFilters;
    perPageOptions?: number[];
    kinds?: InquiryKindOption[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Afspraken')} />
            <InquiryInbox
                title={t('Afspraken')}
                description={t(
                    'Atelierbezoeken en privé consults, nieuwste eerst.',
                )}
                icon={CalendarCheck}
                inquiries={inquiries}
                filters={filters}
                perPageOptions={perPageOptions}
                kinds={kinds}
                routes={appointments as InquiryRouteHelpers}
                emptyLabel={t('Geen aanvragen gevonden.')}
            />
        </>
    );
}

AppointmentsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Afspraken', href: appointments.index(wayfinderLocale()) },
    ],
};
