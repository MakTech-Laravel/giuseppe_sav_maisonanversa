import { Head } from '@inertiajs/react';
import { RealtimeListeners } from '@/components/realtime/realtime-listeners';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <div className="contents font-sans text-base/normal">
            <Head>
                <meta
                    head-key="robots"
                    name="robots"
                    content="noindex, nofollow"
                />
            </Head>
            <RealtimeListeners />
            <AppLayoutTemplate breadcrumbs={breadcrumbs}>
                {children}
            </AppLayoutTemplate>
        </div>
    );
}
