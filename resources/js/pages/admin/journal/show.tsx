import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Eye, Pencil, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { ConfirmDeleteDialog } from '@/components/admin/confirm-delete-dialog';
import { JournalTranslationsDialog } from '@/components/admin/journal-translations-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import journalRoutes from '@/routes/admin/journal';

interface ArticleDetail {
    id: string;
    slug: string;
    title: string;
    excerpt: string;
    body: string;
    image_url: string | null;
    cover_path: string | null;
    category: string | null;
    author: string | null;
    date_label: string | null;
    published_at: string | null;
    sort_order: number;
    is_published: boolean;
    public_url: string;
}

type LocaleCopy = {
    title: string;
    excerpt: string;
    body: string;
    category: string;
    date_label: string;
};

type TranslationStatus = {
    title: boolean;
    excerpt: boolean;
    body: boolean;
    category: boolean;
    date_label: boolean;
};

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

export default function JournalShow({
    article,
    locales,
    translations,
    translationStatus,
}: {
    article: ArticleDetail;
    locales: string[];
    translations: Record<string, LocaleCopy>;
    translationStatus: Record<string, TranslationStatus>;
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();

    return (
        <>
            <Head title={article.title} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={article.title}
                    description={article.excerpt}
                    icon={BookOpen}
                >
                    <Button variant="outline" asChild>
                        <Link href={journalRoutes.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link
                            href={journalRoutes.edit({
                                locale: wayfinderLocale(),
                                article: Number(article.id),
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
                                'Werk dit artikel bij, beheer vertalingen of open de publieke pagina.',
                            )}
                        >
                            <div className="flex flex-col gap-2">
                                <Button asChild className="w-full">
                                    <Link
                                        href={journalRoutes.edit({
                                            locale,
                                            article: Number(article.id),
                                        })}
                                    >
                                        <Pencil className="h-4 w-4" />
                                        {t('Bewerken')}
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    asChild
                                    className="w-full"
                                >
                                    <Link href={article.public_url}>
                                        <Eye className="h-4 w-4" />
                                        {t('Bekijk dit artikel zoals op de site.')}
                                    </Link>
                                </Button>
                                <JournalTranslationsDialog
                                    articleId={article.id}
                                    locales={locales}
                                    translations={translations}
                                    translationStatus={translationStatus}
                                />
                                <ConfirmDeleteDialog
                                    description={t(
                                        'Dit journalartikel wordt permanent verwijderd.',
                                    )}
                                    onConfirm={() =>
                                        router.delete(
                                            journalRoutes.destroy({
                                                locale,
                                                article: Number(article.id),
                                            }).url,
                                        )
                                    }
                                >
                                    <Button
                                        variant="destructive"
                                        className="w-full"
                                    >
                                        <Trash2 className="h-4 w-4" />
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
                            'Zoals bezoekers dit artikel in de huidige taal zien.',
                        )}
                    >
                        <div className="mb-5 flex flex-wrap gap-2">
                            {article.category ? (
                                <Badge variant="secondary">
                                    {article.category}
                                </Badge>
                            ) : null}
                            <Badge variant="secondary">
                                {article.is_published
                                    ? t('Gepubliceerd')
                                    : t('Concept')}
                            </Badge>
                        </div>

                        {article.image_url ? (
                            <img
                                src={article.image_url}
                                alt={article.title}
                                className="mb-5 aspect-4/3 w-full max-w-xl rounded-lg object-cover"
                            />
                        ) : (
                            <div className="mb-5 flex aspect-4/3 w-full max-w-xl items-center justify-center rounded-lg bg-muted text-xs text-muted-foreground">
                                {t('Geen afbeelding')}
                            </div>
                        )}

                        <div className="grid gap-5">
                            <Field
                                label={t('Excerpt')}
                                value={article.excerpt || t('—')}
                                pre
                            />
                            <Field
                                label={t('Body')}
                                value={article.body || t('—')}
                                pre
                            />
                        </div>
                    </AdminPanel>

                    <AdminPanel
                        title={t('Instellingen')}
                        description={t(
                            'Categorie, auteur, publicatie en fallback cover voor dit artikel.',
                        )}
                    >
                        <div className="grid items-start gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            <Field label={t('Slug')} value={article.slug} mono />
                            <Field
                                label={t('Auteur')}
                                value={article.author || t('—')}
                            />
                            <Field
                                label={t('Datumlabel')}
                                value={article.date_label || t('—')}
                            />
                            <Field
                                label={t('Cover asset')}
                                value={article.cover_path || t('Geen')}
                            />
                            <Field
                                label={t('Volgorde')}
                                value={String(article.sort_order)}
                                mono
                            />
                            <Field
                                label={t('Publicatie')}
                                value={
                                    article.published_at
                                        ? new Date(
                                              article.published_at,
                                          ).toLocaleString()
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

JournalShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Journal', href: journalRoutes.index(wayfinderLocale()) },
        { title: 'Gegevens', href: journalRoutes.index(wayfinderLocale()) },
    ],
};
