import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CircleHelp, Pencil, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { FaqTranslationsDialog } from '@/components/admin/faq-translations-dialog';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import faqs from '@/routes/admin/faqs';

interface FaqDetails {
    id: string;
    context: string;
    question: string;
    answer: string;
    sort_order: number;
    is_published: boolean;
}

type LocaleCopy = {
    question: string;
    answer: string;
};

type TranslationStatus = {
    question: boolean;
    answer: boolean;
};

interface ShowFaqProps {
    faq: FaqDetails;
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
                    'wrap-break-word text-sm font-medium',
                    mono && 'font-mono tabular-nums',
                    pre && 'whitespace-pre-wrap',
                )}
            >
                {value}
            </p>
        </div>
    );
}

export default function ShowFaq({
    faq,
    locales,
    translations,
    translationStatus,
}: ShowFaqProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const contextLabel =
        faq.context === 'product'
            ? t('Product')
            : faq.context === 'contact'
              ? t('Contact')
              : faq.context;

    return (
        <>
            <Head title={faq.question} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={faq.question}
                    description={t('Bekijk deze FAQ zoals op de site.')}
                    icon={CircleHelp}
                >
                    <Button variant="outline" asChild>
                        <Link href={faqs.index(locale)}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={faqs.edit({
                                locale,
                                faq: faq.id,
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
                                'Werk deze FAQ bij of ga terug naar de lijst.',
                            )}
                        >
                            <div className="flex flex-col gap-2">
                                <FaqTranslationsDialog
                                    faqId={faq.id}
                                    locales={locales}
                                    translations={translations}
                                    translationStatus={translationStatus}
                                />
                                <Button asChild className="w-full">
                                    <Link
                                        href={faqs.edit({
                                            locale,
                                            faq: faq.id,
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
                                    <Link href={faqs.index(locale)}>
                                        <ArrowLeft className="h-4 w-4" />{' '}
                                        {t('Terug')}
                                    </Link>
                                </Button>
                                <ConfirmDeleteDialog
                                    description={t(
                                        'Deze FAQ wordt permanent verwijderd.',
                                    )}
                                    onConfirm={() =>
                                        router.delete(
                                            faqs.destroy({
                                                locale,
                                                faq: faq.id,
                                            }).url,
                                        )
                                    }
                                >
                                    <Button
                                        variant="destructive"
                                        className="w-full"
                                    >
                                        <Trash2 className="h-4 w-4" />{' '}
                                        {t('Verwijderen')}
                                    </Button>
                                </ConfirmDeleteDialog>
                            </div>
                        </AdminPanel>
                    }
                >
                    <AdminPanel
                        title={t('Inhoud')}
                        description={t(
                            'Zoals bezoekers deze FAQ in de huidige taal zien.',
                        )}
                    >
                        <div className="mb-5 flex flex-wrap gap-2">
                            <Badge variant="secondary">{contextLabel}</Badge>
                            <Badge variant="secondary">
                                {faq.is_published
                                    ? t('Gepubliceerd')
                                    : t('Concept')}
                            </Badge>
                        </div>
                        <div className="grid gap-5">
                            <Field label={t('Vraag')} value={faq.question} pre />
                            <Field label={t('Antwoord')} value={faq.answer} pre />
                        </div>
                    </AdminPanel>

                    <AdminPanel
                        title={t('Instellingen')}
                        description={t(
                            'Context, volgorde en publicatiestatus voor deze FAQ.',
                        )}
                    >
                        <div className="grid items-start gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            <Field label={t('Context')} value={contextLabel} />
                            <Field
                                label={t('Volgorde')}
                                value={String(faq.sort_order)}
                                mono
                            />
                            <Field
                                label={t('Status')}
                                value={
                                    faq.is_published
                                        ? t('Gepubliceerd')
                                        : t('Concept')
                                }
                            />
                        </div>
                    </AdminPanel>
                </AdminResourceShell>
            </div>
        </>
    );
}

ShowFaq.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'FAQ', href: faqs.index(wayfinderLocale()) },
        { title: 'Gegevens', href: '#' },
    ],
};
