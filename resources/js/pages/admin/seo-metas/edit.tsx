import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Globe, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import {
    AdminPanel,
    AdminResourceShell,
} from '@/components/admin/admin-resource-shell';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import seoMetas from '@/routes/admin/seo-metas';

interface SeoMetaEditProps {
    row: {
        id: string;
        page_key: string;
        title: string;
        description: string;
    };
}

export default function EditSeoMeta({ row }: SeoMetaEditProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const form = useForm(
        seoMetas.update({
            locale,
            seoMeta: Number(row.id),
        }),
        {
            title: row.title,
            description: row.description,
        },
    );

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit();
    }

    return (
        <>
            <Head title={t('Edit SEO meta')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Edit SEO meta')}
                    description={row.page_key}
                    icon={Globe}
                >
                    <Button variant="outline" asChild>
                        <Link
                            href={seoMetas.show({
                                locale,
                                seoMeta: Number(row.id),
                            })}
                        >
                            <ArrowLeft className="h-4 w-4" /> {t('Back')}
                        </Link>
                    </Button>
                </AdminPageHeader>

                <form onSubmit={submit} className="w-full space-y-6">
                    <AdminResourceShell
                        aside={
                            <AdminPanel title={t('Page')}>
                                <div className="grid gap-4">
                                    <div className="grid gap-2">
                                        <Label>{t('Page')}</Label>
                                        <p className="rounded-md border bg-muted/40 px-3 py-2 font-mono text-sm">
                                            {row.page_key}
                                        </p>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {t(
                                            'Copy on this form is the Dutch source. DeepL fills EN and FR after saving.',
                                        )}
                                    </p>
                                </div>
                            </AdminPanel>
                        }
                    >
                        <AdminPanel
                            title={t('Content')}
                            description={t(
                                'Edit the SEO title and description for this page.',
                            )}
                        >
                            <div className="grid gap-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="title">{t('Title')}</Label>
                                    <Input
                                        id="title"
                                        value={form.data.title}
                                        onChange={(event) =>
                                            form.setData(
                                                'title',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={form.errors.title} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="description">
                                        {t('Description')}
                                    </Label>
                                    <Textarea
                                        id="description"
                                        value={form.data.description}
                                        onChange={(event) =>
                                            form.setData(
                                                'description',
                                                event.target.value,
                                            )
                                        }
                                        className="min-h-32 resize-y"
                                    />
                                    <InputError
                                        message={form.errors.description}
                                    />
                                </div>
                            </div>
                        </AdminPanel>
                    </AdminResourceShell>

                    <div className="flex flex-wrap items-center gap-3">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : null}
                            {t('Save')}
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={seoMetas.show({
                                    locale,
                                    seoMeta: Number(row.id),
                                })}
                            >
                                {t('Cancel')}
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

EditSeoMeta.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'SEO Meta', href: seoMetas.index(wayfinderLocale()) },
        { title: 'Bewerken', href: '#' },
    ],
};
