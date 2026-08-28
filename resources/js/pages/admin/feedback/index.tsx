import { Head } from '@inertiajs/react';
import { MessageSquareQuote } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { InquiryInbox } from '@/components/admin/inquiry-inbox';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import feedback from '@/routes/admin/feedback';
import type { Paginated } from '@/types/admin';
import type {
    InquiryFilters,
    InquiryListItem,
    InquiryRouteHelpers,
} from '@/types/inquiry';

export default function FeedbackIndex({
    inquiries,
    filters,
    perPageOptions = [10, 15, 25, 50, 100],
}: {
    inquiries: Paginated<InquiryListItem>;
    filters: InquiryFilters;
    perPageOptions?: number[];
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Feedback')} />
            <InquiryInbox
                title={t('Feedback')}
                description={t('Feedback van bezoekers, nieuwste eerst.')}
                icon={MessageSquareQuote}
                inquiries={inquiries}
                filters={filters}
                perPageOptions={perPageOptions}
                routes={feedback as InquiryRouteHelpers}
                emptyLabel={t('Geen aanvragen gevonden.')}
            />
        </>
    );
}

FeedbackIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Feedback', href: feedback.index(wayfinderLocale()) },
    ],
};
