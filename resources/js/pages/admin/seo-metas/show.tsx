import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Globe, Pencil } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { SeoMetaTranslationsDialog } from '@/components/admin/seo-meta-translations-dialog';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import seoMetas from '@/routes/admin/seo-metas';

interface SeoMetaDetails {
    id: string;
    page_key: string;
    title: string;
    description: string;
}

type LocaleCopy = {
    title: string;
    description: string;
};

type TranslationStatus = {
    title: boolean;
    description: boolean;
};

interface ShowSeoMetaProps {
    row: SeoMetaDetails;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}

function Field({
    label,
    value,
    mono = false,
    pre = false,
}: {
    label: string;
    value: string;
    mono?: boolean;
    pre?: boolean;
}) {
    return (
        <div className="grid min-w-0 gap-1">
            <p className="text-xs tracking-wide text-muted-foreground uppercase">
                {label}
            </p>
            <p
                className={cn(
                    'text-sm font-medium wrap-break-word',
                    mono && 'font-mono tabular-nums',
                    pre && 'whitespace-pre-wrap',
                )}
            >
                {value}
            </p>
        </div>
    );
}

export default function ShowSeoMeta({
    row,
    locales,
    translations,
    translationStatus,
}: ShowSeoMetaProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    return (
        <>
            <Head title={row.title} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={row.title}
                    description={t('Bekijk deze SEO-meta zoals op de site.')}
                    icon={Globe}
                >
                    <Button variant="outline" asChild>
                        <Link href={seoMetas.index(locale)}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={seoMetas.edit({
                                locale,
                                seoMeta: Number(row.id),
                            })}
                        >
                            <Pencil className="h-4 w-4" /> {t('Bewerken')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <AdminResourceShell
                    aside={
                        <AdminPanel
                            title={t('Acties')}
                            description={t(
                                'Werk deze SEO-meta bij of ga terug naar de lijst.',
                            )}
                        >
                            <div className="flex flex-col gap-2">
                                <SeoMetaTranslationsDialog
                                    seoMetaId={row.id}
                                    locales={locales}
                                    translations={translations}
                                    translationStatus={translationStatus}
                                />
                                <Button asChild className="w-full">
                                    <Link
                                        href={seoMetas.edit({
                                            locale,
                                            seoMeta: Number(row.id),
                                        })}
                                    >
                                        <Pencil className="h-4 w-4" />{' '}
                                        {t('Bewerken')}
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    asChild
                                    className="w-full"
                                >
                                    <Link href={seoMetas.index(locale)}>
                                        <ArrowLeft className="h-4 w-4" />{' '}
                                        {t('Terug')}
                                    </Link>
                                </Button>
                            </div>
                        </AdminPanel>
                    }
                >
                    <AdminPanel
                        title={t('Inhoud')}
                        description={t(
                            'Zoals bezoekers deze pagina in de huidige taal zien.',
                        )}
                    >
                        <div className="grid gap-5">
                            <Field
                                label={t('Pagina')}
                                value={row.page_key}
                                mono
                            />
                            <Field label={t('Titel')} value={row.title} pre />
                            <Field
                                label={t('Beschrijving')}
                                value={row.description}
                                pre
                            />
                        </div>
                    </AdminPanel>
                </AdminResourceShell>
            </div>
        </>
    );
}

ShowSeoMeta.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'SEO Meta', href: seoMetas.index(wayfinderLocale()) },
        { title: 'Gegevens', href: '#' },
    ],
};
