import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type PartnerClubValues = {
    id: string;
    city: string;
    country: string;
    status: string;
    sort_order: number;
    is_published: boolean;
};

const STATUSES = [
    { value: 'in_discussion', label: 'In gesprek' },
    { value: 'open', label: 'Open' },
    { value: 'active', label: 'Actief' },
];

type PartnerClubFormProps = {
    club?: PartnerClubValues;
    action: string;
    method: 'post' | 'put';
    submitLabel: string;
};

export function PartnerClubForm({
    club,
    action,
    method,
    submitLabel,
}: PartnerClubFormProps) {
    const { t } = useTranslation();

    const form = useForm({
        city: club?.city ?? '',
        country: club?.country ?? '',
        status: club?.status ?? 'in_discussion',
        sort_order: club?.sort_order ?? 0,
        is_published: club?.is_published ?? false,
    });

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (method === 'put') {
            form.put(action);

            return;
        }

        form.post(action);
    }

    return (
        <form onSubmit={handleSubmit} className="max-w-xl space-y-5">
            <div className="space-y-1.5">
                <Label htmlFor="partner-club-city">{t('Stad')}</Label>
                <Input
                    id="partner-club-city"
                    value={form.data.city}
                    onChange={(event) =>
                        form.setData('city', event.target.value)
                    }
                    required
                />
                {form.errors.city && (
                    <p className="text-sm text-destructive">
                        {form.errors.city}
                    </p>
                )}
            </div>

            <div className="space-y-1.5">
                <Label htmlFor="partner-club-country">{t('Land')}</Label>
                <Input
                    id="partner-club-country"
                    value={form.data.country}
                    onChange={(event) =>
                        form.setData('country', event.target.value)
                    }
                    required
                />
                {form.errors.country && (
                    <p className="text-sm text-destructive">
                        {form.errors.country}
                    </p>
                )}
            </div>

            <div className="space-y-1.5">
                <Label>{t('Status')}</Label>
                <Select
                    value={form.data.status}
                    onValueChange={(value) => form.setData('status', value)}
                >
                    <SelectTrigger className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {STATUSES.map((status) => (
                            <SelectItem key={status.value} value={status.value}>
                                {t(status.label)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {form.errors.status && (
                    <p className="text-sm text-destructive">
                        {form.errors.status}
                    </p>
                )}
            </div>

            <div className="space-y-1.5">
                <Label htmlFor="partner-club-sort">{t('Volgorde')}</Label>
                <Input
                    id="partner-club-sort"
                    type="number"
                    min={0}
                    value={form.data.sort_order}
                    onChange={(event) =>
                        form.setData('sort_order', Number(event.target.value))
                    }
                />
                {form.errors.sort_order && (
                    <p className="text-sm text-destructive">
                        {form.errors.sort_order}
                    </p>
                )}
            </div>

            <label className="flex items-center gap-2 text-sm">
                <Checkbox
                    checked={form.data.is_published}
                    onCheckedChange={(checked) =>
                        form.setData('is_published', checked === true)
                    }
                />
                {t('Gepubliceerd')}
            </label>

            <Button type="submit" disabled={form.processing}>
                {submitLabel}
            </Button>
        </form>
    );
}
