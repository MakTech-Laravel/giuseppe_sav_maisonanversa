import { Head } from '@inertiajs/react';
import { MessageCircle, Sparkles } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import community from '@/routes/admin/community';

export default function CommunityIndex({ items }: { items: unknown[] }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Gemeenschap')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Gemeenschap')}
                    description={t(
                        'Moderatie van ledenconversaties en activiteit.',
                    )}
                    icon={MessageCircle}
                />
                <div className="rounded-xl border bg-card px-6 py-16 text-center shadow-sm">
                    <Sparkles className="mx-auto h-10 w-10 text-primary/50" />
                    <h2 className="mt-4 font-semibold">
                        {t('Communitymoderatie komt binnenkort')}
                    </h2>
                    <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                        {t(
                            'Ledenconversaties en moderatietools verschijnen hier zodra de communityservice is gekoppeld.',
                        )}
                    </p>
                    {items.length > 0 && (
                        <p className="mt-3 text-xs text-muted-foreground">
                            {t('{{count}} items in wachtrij', {
                                count: items.length,
                            })}
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
        { title: 'Gemeenschap', href: community.index(wayfinderLocale()) },
    ],
};
