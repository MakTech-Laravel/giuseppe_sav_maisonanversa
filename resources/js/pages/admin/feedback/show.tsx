import { Head } from '@inertiajs/react';
import { MessageSquareQuote } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { InquiryShow } from '@/components/admin/inquiry-show';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import feedback from '@/routes/admin/feedback';
import type { InquiryDetails, InquiryRouteHelpers } from '@/types/inquiry';

export default function FeedbackShow({ inquiry }: { inquiry: InquiryDetails }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={inquiry.name} />
            <InquiryShow
                title={inquiry.name}
                description={t('Bekijk deze feedback.')}
                icon={MessageSquareQuote}
                inquiry={inquiry}
                routes={feedback as InquiryRouteHelpers}
            />
        </>
    );
}

FeedbackShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Feedback', href: feedback.index(wayfinderLocale()) },
        { title: 'Gegevens', href: '#' },
    ],
};
