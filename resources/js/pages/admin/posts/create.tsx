import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, FilePlus2, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import posts from '@/routes/admin/posts';

export default function CreatePost() {
    const { t } = useTranslation();
    const form = useForm(posts.store(wayfinderLocale()), { title: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit();
    };

    return (
        <>
            <Head title={t('Bericht aanmaken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Bericht aanmaken')}
                    description={t('Maak een nieuw contentitem aan.')}
                    icon={FilePlus2}
                >
                    <Button variant="outline" asChild>
                        <Link href={posts.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug naar berichten')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <form
                    onSubmit={submit}
                    className="w-full space-y-5 rounded-xl border bg-card p-6 shadow-sm md:p-8"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="title">{t('Titel')}</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                            autoFocus
                        />
                        <InputError message={form.errors.title} />
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {t('Bericht aanmaken')}
                    </Button>
                </form>
            </div>
        </>
    );
}

CreatePost.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'Berichten', href: posts.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: posts.create(wayfinderLocale()) },
    ],
};
