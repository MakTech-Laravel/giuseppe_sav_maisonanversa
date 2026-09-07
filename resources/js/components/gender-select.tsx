import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { USER_GENDER_OPTIONS } from '@/lib/user-gender';
import { cn } from '@/lib/utils';

type GenderSelectProps = {
    id?: string;
    name?: string;
    defaultValue?: string;
    options?: readonly { value: string; label: string }[];
    placeholder?: string;
    triggerClassName?: string;
    'aria-label'?: string;
    tabIndex?: number;
};

export function GenderSelect({
    id = 'gender',
    name = 'gender',
    defaultValue = '',
    options = USER_GENDER_OPTIONS,
    placeholder,
    triggerClassName,
    'aria-label': ariaLabel,
    tabIndex,
}: GenderSelectProps) {
    const { t } = useTranslation();
    const [value, setValue] = useState(defaultValue);
    const label = placeholder ?? t('Geslacht');

    return (
        <>
            <input type="hidden" name={name} value={value} />
            <Select
                value={value || undefined}
                onValueChange={setValue}
                required
                // MaisonModal uses a custom focus trap; keep Select non-modal so
                // the portaled list remains interactive inside that dialog.
                modal={false}
            >
                <SelectTrigger
                    id={id}
                    tabIndex={tabIndex}
                    aria-label={ariaLabel ?? label}
                    className={cn('w-full', triggerClassName)}
                >
                    <SelectValue placeholder={label} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {t(option.label)}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    );
}
