import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Eye, FileText, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import { LegalRichTextEditor } from '@/components/admin/legal-rich-text-editor';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import legalPages from '@/routes/admin/legal-pages';

interface LegalPageEditProps {
    page: {
        id: string;
        slug: string;
        body: string;
        is_published: boolean;
    };
}

export default function EditLegalPage({ page }: LegalPageEditProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const form = useForm(
        legalPages.update({
            locale,
            legalPage: Number(page.id),
        }),
        {
            body: page.body,
            is_published: page.is_published,
        },
    );

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit();
    }

    const slugLabels: Record<string, string> = {
        privacy: t('Privacybeleid'),
        terms: t('Algemene voorwaarden'),
        shipping: t('Verzending & Retour'),
        care: t('Zorg & Garantie'),
    };

    return (
        <>
            <Head title={t('Juridische pagina bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Juridische pagina bewerken')}
                    description={slugLabels[page.slug] ?? page.slug}
                    icon={FileText}
                >
                    <Button variant="outline" asChild>
                        <Link href={legalPages.index(locale)}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link
                            href={legalPages.show({
                                locale,
                                legalPage: Number(page.id),
                            })}
                        >
                            <Eye className="h-4 w-4" /> {t('Voorbeeld')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <form onSubmit={submit} className="w-full space-y-6">
                    <AdminResourceShell
                        aside={
                            <AdminPanel title={t('Publicatie')}>
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label>{t('Slug')}</Label>
                                        <p className="rounded-md border bg-muted/40 px-3 py-2 font-mono text-sm">
                                            {page.slug}
                                        </p>
                                    </div>
                                    <label
                                        htmlFor="is_published"
                                        className="flex min-h-9 cursor-pointer items-center gap-3 rounded-md border px-3 py-2 text-sm"
                                    >
                                        <input
                                            id="is_published"
                                            type="checkbox"
                                            className="size-4 accent-primary"
                                            checked={form.data.is_published}
                                            onChange={(event) =>
                                                form.setData(
                                                    'is_published',
                                                    event.target.checked,
                                                )
                                            }
                                        />
                                        <span>
                                            {form.data.is_published
                                                ? t('Gepubliceerd')
                                                : t('Concept')}
                                        </span>
                                    </label>
                                    <p className="text-xs text-muted-foreground">
                                        {t(
                                            'Tekst op dit formulier is de Nederlandse bron. DeepL vult EN en FR na opslaan. HTML-tags blijven ongewijzigd.',
                                        )}
                                    </p>
                                    <InputError
                                        message={form.errors.is_published}
                                    />
                                </div>
                            </AdminPanel>
                        }
                    >
                        <AdminPanel
                            title={t('Inhoud')}
                            description={t(
                                'Bewerk visueel of schakel naar HTML. Onveilige code wordt verwijderd.',
                            )}
                        >
                            <LegalRichTextEditor
                                value={form.data.body}
                                onChange={(body) => form.setData('body', body)}
                                error={form.errors.body}
                            />
                            <InputError
                                message={form.errors.body}
                                className="mt-3"
                            />
                        </AdminPanel>
                    </AdminResourceShell>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : null}
                            {t('Opslaan')}
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={legalPages.show({
                                    locale,
                                    legalPage: Number(page.id),
                                })}
                            >
                                {t('Annuleren')}
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

EditLegalPage.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: "Juridische Pagina's",
            href: legalPages.index(wayfinderLocale()),
        },
        { title: 'Bewerken', href: '#' },
    ],
};
