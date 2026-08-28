import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, FileText, Pencil } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { LegalPageTranslationsDialog } from '@/components/admin/legal-page-translations-dialog';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { LegalPageLayout } from '@/components/maison/legal/legal-page-layout';
import { LegalPageLinks } from '@/components/maison/legal/legal-page-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import legalPages from '@/routes/admin/legal-pages';

type LocaleCopy = {
    body: string;
};

type TranslationStatus = {
    body: boolean;
};

interface ShowLegalPageProps {
    page: {
        id: string;
        slug: string;
        is_published: boolean;
    };
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}

const SLUG_TITLES: Record<string, string> = {
    privacy: 'Privacybeleid',
    terms: 'Algemene voorwaarden',
    shipping: 'Verzending & Retour',
    care: 'Zorg & Garantie',
};

const LOCALE_LABELS: Record<string, string> = {
    nl: 'Nederlands',
    en: 'English',
    fr: 'Français',
};

export default function ShowLegalPage({
    page,
    locales,
    translations,
    translationStatus,
}: ShowLegalPageProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [previewLocale, setPreviewLocale] = useState(
        locales.includes(locale) ? locale : (locales[0] ?? 'nl'),
    );
    const title = SLUG_TITLES[page.slug] ?? page.slug;
    const previewBody = translations[previewLocale]?.body ?? '';

    return (
        <>
            <Head title={t(title)} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t(title)}
                    description={t(
                        'Bekijk deze juridische pagina zoals bezoekers die zien.',
                    )}
                    icon={FileText}
                >
                    <Button variant="outline" asChild>
                        <Link href={legalPages.index(locale)}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={legalPages.edit({
                                locale,
                                legalPage: Number(page.id),
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
                                'Pas vertalingen aan of bewerk de Nederlandse bron.',
                            )}
                        >
                            <div className="flex flex-col gap-2">
                                <LegalPageTranslationsDialog
                                    pageId={page.id}
                                    locales={locales}
                                    translations={translations}
                                    translationStatus={translationStatus}
                                />
                                <Button asChild className="w-full">
                                    <Link
                                        href={legalPages.edit({
                                            locale,
                                            legalPage: Number(page.id),
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
                                    <Link href={legalPages.index(locale)}>
                                        <ArrowLeft className="h-4 w-4" />{' '}
                                        {t('Terug')}
                                    </Link>
                                </Button>
                            </div>
                        </AdminPanel>
                    }
                >
                    <AdminPanel
                        title={t('Voorbeeld')}
                        description={t(
                            'Schakel tussen talen om de publieke pagina te controleren.',
                        )}
                    >
                        <div className="mb-5 flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {page.is_published
                                    ? t('Gepubliceerd')
                                    : t('Concept')}
                            </Badge>
                            {locales.map((code) => (
                                <Button
                                    key={code}
                                    type="button"
                                    size="sm"
                                    variant={
                                        previewLocale === code
                                            ? 'default'
                                            : 'outline'
                                    }
                                    onClick={() => setPreviewLocale(code)}
                                >
                                    {LOCALE_LABELS[code] ?? code.toUpperCase()}
                                </Button>
                            ))}
                        </div>
                        <div className="legal-preview-surface overflow-hidden rounded-xl border">
                            <LegalPageLayout
                                title={title}
                                body={previewBody}
                                preview
                            >
                                <LegalPageLinks slug={page.slug} preview />
                            </LegalPageLayout>
                        </div>
                    </AdminPanel>
                </AdminResourceShell>
            </div>
        </>
    );
}

ShowLegalPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: "Juridische Pagina's",
            href: legalPages.index(wayfinderLocale()),
        },
        { title: 'Voorbeeld', href: '#' },
    ],
};
