import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Loader2, Pencil } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { DressingItemFormFields } from '@/components/admin/dressing-item-form-fields';
import type { DressingItemFormData } from '@/components/admin/dressing-item-form-fields';
import { Button } from '@/components/ui/button';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import dressingItems from '@/routes/admin/dressing-items';

interface DressingItemDetails {
    id: string;
    name: string;
    slug: string;
    category: string;
    description: string;
    image_key: string | null;
    image_url: string | null;
    status: 'coming_soon' | 'available';
    sort_order: number;
    is_published: boolean;
}

export default function EditDressingItem({
    item,
}: {
    item: DressingItemDetails;
}) {
    const { t } = useTranslation();
    const form = useForm(
        dressingItems.update({
            locale: wayfinderLocale(),
            dressingItem: item.id,
        }),
        {
            name: item.name,
            slug: item.slug,
            category: item.category,
            description: item.description,
            status: item.status,
            sort_order: String(item.sort_order),
            is_published: item.is_published,
            image_key: item.image_key ?? '',
            image: null as File | null,
            remove_image: false,
        } satisfies DressingItemFormData,
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.submit({ forceFormData: true });
    };

    return (
        <>
            <Head title={t('Dressing item bewerken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('Dressing item bewerken')}
                    description={t('Werk dit item van de Kleedkamer bij.')}
                    icon={Pencil}
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
                        existingImageUrl={item.image_url}
                    />
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {t('Wijzigingen opslaan')}
                    </Button>
                </form>
            </div>
        </>
    );
}

EditDressingItem.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        {
            title: 'Kleedkamer',
            href: dressingItems.index(wayfinderLocale()),
        },
        { title: 'Bewerken', href: dressingItems.index(wayfinderLocale()) },
    ],
};
