import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Shirt } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { DressingItemFormFields } from '@/components/admin/dressing-item-form-fields';
import type { DressingItemFormData } from '@/components/admin/dressing-item-form-fields';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import dressingItems from '@/routes/admin/dressing-items';

const defaults: DressingItemFormData = {
    name: '',
    slug: '',
    category: '',
    description: '',
    status: 'coming_soon',
    sort_order: '0',
    is_published: true,
    image_key: '',
    image: null,
    remove_image: false,
};

export default function CreateDressingItem() {
    const { t } = useTranslation();
    const form = useForm(dressingItems.store(wayfinderLocale()), defaults);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit({ forceFormData: true });
    };

    return (
        <>
            <Head title={t('Dressing item aanmaken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Dressing item aanmaken')}
                    description={t('Voeg een item toe aan de Kleedkamer.')}
                    icon={Shirt}
                >
                    <Button variant="outline" asChild>
                        <Link href={dressingItems.index(wayfinderLocale())}>
                            <ArrowLeft className="h-4 w-4" />{' '}
                            {t('Terug naar Kleedkamer')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <form onSubmit={submit} className="w-full space-y-6">
                    <DressingItemFormFields
                        data={form.data}
                        errors={form.errors}
                        setData={form.setData}
                    />
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {t('Item aanmaken')}
                    </Button>
                </form>
            </div>
        </>
    );
}

CreateDressingItem.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Kleedkamer',
            href: dressingItems.index(wayfinderLocale()),
        },
        {
            title: 'Aanmaken',
            href: dressingItems.create(wayfinderLocale()),
        },
    ],
};
