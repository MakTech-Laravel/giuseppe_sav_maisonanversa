import { Head } from '@inertiajs/react';
import { MessageCircle, Sparkles } from 'lucide-react';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import community from '@/routes/admin/community';

export default function CommunityIndex({ items }: { items: unknown[] }) {
    return (
        <>
            <Head title="Community" />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title="Community"
                    description="Moderate member conversations and activity."
                    icon={MessageCircle}
                />
                <div className="rounded-xl border bg-card px-6 py-16 text-center shadow-sm">
                    <Sparkles className="mx-auto h-10 w-10 text-primary/50" />
                    <h2 className="mt-4 font-semibold">
                        Community moderation is coming soon
                    </h2>
                    <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                        Member conversations and moderation tools will appear
                        here when the community service is connected.
                    </p>
                    {items.length > 0 && (
                        <p className="mt-3 text-xs text-muted-foreground">
                            {items.length} queued items
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}

CommunityIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Community', href: community.index(wayfinderLocale()) },
    ],
};
