import { useTranslation } from 'react-i18next';
import { AdminPanel } from '@/components/admin/admin-resource-shell';
import InputError from '@/components/input-error';
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

export type FaqFormData = {
    context: string;
    question: string;
    answer: string;
    sort_order: number;
    is_published: boolean;
};

export type FaqFormErrors = Partial<Record<keyof FaqFormData, string>>;

export type FaqContextOption = {
    value: string;
    label: string;
};

function contextLabel(
    value: string,
    t: (key: string) => string,
    fallback?: string,
): string {
    if (value === 'product') {
        return t('Product');
    }

    if (value === 'contact') {
        return t('Contact');
    }

    return fallback ?? value;
}

export function FaqFormFields({
    data,
    errors,
    contexts,
    setData,
}: {
    data: FaqFormData;
    errors: FaqFormErrors;
    contexts: FaqContextOption[];
    setData: <K extends keyof FaqFormData>(
        key: K,
        value: FaqFormData[K],
    ) => void;
}) {
    const { t } = useTranslation();

    return (
        <div className="grid w-full gap-6">
            <AdminPanel
                title={t('Content')}
                description={t(
                    'Question and answer as visitors see them on the site.',
                )}
            >
                <div className="grid gap-5">
                    <div className="grid min-w-0 gap-2">
                        <Label htmlFor="question">{t('Question')}</Label>
                        <Textarea
                            id="question"
                            value={data.question}
                            onChange={(event) =>
                                setData('question', event.target.value)
                            }
                            className="min-h-24 resize-y md:min-h-28"
                            autoFocus
                        />
                        <InputError message={errors.question} />
                    </div>
                    <div className="grid min-w-0 gap-2">
                        <Label htmlFor="answer">{t('Answer')}</Label>
                        <Textarea
                            id="answer"
                            value={data.answer}
                            onChange={(event) =>
                                setData('answer', event.target.value)
                            }
                            className="min-h-40 resize-y md:min-h-52"
                        />
                        <InputError message={errors.answer} />
                    </div>
                </div>
            </AdminPanel>

            <AdminPanel
                title={t('Settings')}
                description={t(
                    'Context, order, and publication status for this FAQ.',
                )}
            >
                <div className="grid items-start gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <div className="grid min-w-0 gap-2">
                        <Label htmlFor="context">{t('Context')}</Label>
                        <Select
                            value={data.context}
                            onValueChange={(value) => setData('context', value)}
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
                                        {contextLabel(
                                            option.value,
                                            t,
                                            option.label,
                                        )}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.context} />
                    </div>

                    <div className="grid min-w-0 gap-2">
                        <Label htmlFor="sort_order">{t('Order')}</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            onChange={(event) =>
                                setData(
                                    'sort_order',
                                    Number(event.target.value),
                                )
                            }
                        />
                        <p className="text-xs text-muted-foreground">
                            {t(
                                'Lower = earlier. Order applies per context (product or contact).',
                            )}
                        </p>
                        <InputError message={errors.sort_order} />
                    </div>

                    <div className="grid min-w-0 gap-2 sm:col-span-2 xl:col-span-1">
                        <Label htmlFor="is_published">{t('Status')}</Label>
                        <label
                            htmlFor="is_published"
                            className="flex min-h-9 cursor-pointer items-center gap-3 rounded-md border px-3 py-2 text-sm"
                        >
                            <input
                                id="is_published"
                                type="checkbox"
                                className="size-4 accent-primary"
                                checked={data.is_published}
                                onChange={(event) =>
                                    setData(
                                        'is_published',
                                        event.target.checked,
                                    )
                                }
                            />
                            <span>
                                {data.is_published
                                    ? t('Published')
                                    : t('Draft')}
                            </span>
                        </label>
                        <InputError message={errors.is_published} />
                    </div>
                </div>
            </AdminPanel>
        </div>
    );
}

export function FaqFormAside({
    isEdit = false,
    faq,
}: {
    isEdit?: boolean;
    faq?: {
        id: string;
        context: string;
        is_published: boolean;
        sort_order: number;
    };
}) {
    const { t } = useTranslation();

    return (
        <>
            <AdminPanel title={t('Tips')}>
                <ul className="space-y-3 text-sm text-muted-foreground">
                    <li>
                        {t(
                            'Product FAQs appear on the product page; contact FAQs on the contact page.',
                        )}
                    </li>
                    <li>
                        {t(
                            'Order restarts per context — 0, 1, 2… for product and again for contact.',
                        )}
                    </li>
                    <li>
                        {t(
                            'Text on this form is the source. DeepL fills NL, EN, and FR after saving. Adjust per language via Translations.',
                        )}
                    </li>
                    <li>
                        {t(
                            'Only published FAQs are visible on the website and in SEO.',
                        )}
                    </li>
                </ul>
            </AdminPanel>

            {isEdit && faq ? (
                <AdminPanel title={t('Overview')}>
                    <dl className="space-y-3 text-sm">
                        <div className="flex justify-between gap-3">
                            <dt className="text-muted-foreground">{t('ID')}</dt>
                            <dd className="font-medium">#{faq.id}</dd>
                        </div>
                        <div className="flex justify-between gap-3">
                            <dt className="text-muted-foreground">
                                {t('Context')}
                            </dt>
                            <dd className="font-medium">
                                {contextLabel(faq.context, t)}
                            </dd>
                        </div>
                        <div className="flex justify-between gap-3">
                            <dt className="text-muted-foreground">
                                {t('Order')}
                            </dt>
                            <dd className="font-medium">{faq.sort_order}</dd>
                        </div>
                        <div className="flex justify-between gap-3">
                            <dt className="text-muted-foreground">
                                {t('Status')}
                            </dt>
                            <dd className="font-medium">
                                {faq.is_published
                                    ? t('Published')
                                    : t('Draft')}
                            </dd>
                        </div>
                    </dl>
                </AdminPanel>
            ) : null}
        </>
    );
}
