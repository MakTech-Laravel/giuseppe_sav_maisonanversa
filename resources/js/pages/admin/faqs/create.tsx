import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CircleHelp, Loader2 } from 'lucide-react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { AdminPageHeader } from '@/components/admin/admin-page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { dashboard } from '@/routes/admin';
import faqs from '@/routes/admin/faqs';

interface ContextOption {
    value: string;
    label: string;
}

export default function CreateFaq({
    contexts,
}: {
    contexts: ContextOption[];
}) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const form = useForm(faqs.store(locale), {
        context: contexts[0]?.value ?? 'product',
        question: '',
        answer: '',
        sort_order: 0,
        is_published: true,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        form
            .transform((data) => ({
                ...data,
                sort_order: Number(data.sort_order),
            }))
            .submit();
    }

    return (
        <>
            <Head title={t('FAQ aanmaken')} />
            <div className="w-full space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                <AdminPageHeader
                    title={t('FAQ aanmaken')}
                    description={t('Voeg een nieuwe veelgestelde vraag toe.')}
                    icon={CircleHelp}
                >
                    <Button variant="outline" asChild>
                        <Link href={faqs.index(locale)}>
                            <ArrowLeft className="h-4 w-4" /> {t('Terug')}
                        </Link>
                    </Button>
                </AdminPageHeader>
                <form
                    onSubmit={submit}
                    className="w-full max-w-2xl space-y-5 rounded-xl border bg-card p-6 shadow-sm md:p-8"
                >
                    <div className="space-y-2">
                        <Label htmlFor="context">{t('Context')}</Label>
                        <Select
                            value={form.data.context}
                            onValueChange={(value) =>
                                form.setData('context', value)
                            }
                        >
                            <SelectTrigger id="context" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {contexts.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.value === 'product'
                                            ? t('Product')
                                            : option.value === 'contact'
                                              ? t('Contact')
                                              : option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {form.errors.context ? (
                            <p className="text-sm text-destructive">
                                {form.errors.context}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="question">{t('Vraag')}</Label>
                        <Textarea
                            id="question"
                            value={form.data.question}
                            onChange={(event) =>
                                form.setData('question', event.target.value)
                            }
                            className="min-h-20"
                        />
                        {form.errors.question ? (
                            <p className="text-sm text-destructive">
                                {form.errors.question}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="answer">{t('Antwoord')}</Label>
                        <Textarea
                            id="answer"
                            value={form.data.answer}
                            onChange={(event) =>
                                form.setData('answer', event.target.value)
                            }
                            className="min-h-32"
                        />
                        {form.errors.answer ? (
                            <p className="text-sm text-destructive">
                                {form.errors.answer}
                            </p>
                        ) : null}
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="sort_order">{t('Volgorde')}</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={form.data.sort_order}
                            onChange={(event) =>
                                form.setData(
                                    'sort_order',
                                    Number(event.target.value),
                                )
                            }
                        />
                        {form.errors.sort_order ? (
                            <p className="text-sm text-destructive">
                                {form.errors.sort_order}
                            </p>
                        ) : null}
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.is_published}
                            onChange={(event) =>
                                form.setData(
                                    'is_published',
                                    event.target.checked,
                                )
                            }
                        />
                        {t('Gepubliceerd')}
                    </label>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : null}
                        {t('Opslaan')}
                    </Button>
                </form>
            </div>
        </>
    );
}

CreateFaq.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard(wayfinderLocale()) },
        { title: 'FAQ', href: faqs.index(wayfinderLocale()) },
        { title: 'Aanmaken', href: faqs.create(wayfinderLocale()) },
    ],
};
