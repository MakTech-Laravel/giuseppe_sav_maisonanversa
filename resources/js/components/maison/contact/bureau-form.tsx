import type { FormEvent, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { submitBureauMailto } from '@/components/maison/contact/bureau-mailto';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { cn } from '@/lib/utils';

const fieldClassName =
    'w-full rounded border border-gold/25 bg-black/30 px-3.5 py-3 font-sans text-[13px] text-cream outline-none focus:border-gold';

type BureauFormProps = {
    subject: string;
    submitLabel: string;
    children: ReactNode;
    className?: string;
};

export function BureauForm({
    subject,
    submitLabel,
    children,
    className,
}: BureauFormProps) {
    const { t } = useTranslation();

    function onSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!event.currentTarget.reportValidity()) {
            return;
        }

        submitBureauMailto(event.currentTarget, t(subject));
    }

    return (
        <form
            onSubmit={onSubmit}
            className={cn('mt-4.5 grid gap-3', className)}
        >
            {children}

            <MaisonButton type="submit" variant="gold" className="mt-1">
                {t(submitLabel)}
            </MaisonButton>
        </form>
    );
}

export function BureauFieldRow({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'grid gap-3 ma-sm:grid-cols-2',
                className,
            )}
        >
            {children}
        </div>
    );
}

export function BureauInput({
    name,
    type = 'text',
    placeholder,
    required = false,
}: {
    name: string;
    type?: 'text' | 'email' | 'date';
    placeholder: string;
    required?: boolean;
}) {
    const { t } = useTranslation();

    return (
        <input
            type={type}
            name={name}
            required={required}
            placeholder={t(placeholder)}
            className={fieldClassName}
        />
    );
}

export function BureauSelect({
    name,
    options,
    className,
}: {
    name: string;
    options: readonly string[];
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <select name={name} className={cn(fieldClassName, className)}>
            {options.map((option) => (
                <option key={option} value={option === options[0] ? '' : option}>
                    {t(option)}
                </option>
            ))}
        </select>
    );
}

export function BureauTextarea({
    name,
    placeholder,
    rows = 3,
}: {
    name: string;
    placeholder: string;
    rows?: number;
}) {
    const { t } = useTranslation();

    return (
        <textarea
            name={name}
            rows={rows}
            placeholder={t(placeholder)}
            className={cn(fieldClassName, 'col-span-full resize-y')}
        />
    );
}
