import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { SuccessPanel } from '@/components/maison/ui/success-panel';
import { useLocale } from '@/hooks/use-locale';
import { cn } from '@/lib/utils';
import { store as storeContact } from '@/routes/maison/contact';

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
    const { locale } = useLocale();

    return (
        <Form
            {...storeContact.form(locale)}
            className={cn('mt-4.5 grid gap-3', className)}
            options={{ preserveScroll: true }}
            resetOnSuccess
        >
            {({ processing, recentlySuccessful, errors }) =>
                recentlySuccessful ? (
                    <SuccessPanel
                        title={t('Uw bericht is ontvangen.')}
                        icon="✓"
                        className="text-left [&_div]:text-sand [&_h3]:text-cream"
                    >
                        <p>{t('Wij bevestigen persoonlijk zo snel mogelijk.')}</p>
                    </SuccessPanel>
                ) : (
                    <>
                        <input type="hidden" name="subject" value={t(subject)} />
                        <input
                            type="text"
                            name="website"
                            tabIndex={-1}
                            autoComplete="off"
                            className="hidden"
                            defaultValue=""
                        />
                        {children}
                        {(errors.name || errors.email || errors.message) && (
                            <p role="alert" className="text-[13px] text-gold">
                                {errors.name || errors.email || errors.message}
                            </p>
                        )}
                        <MaisonButton
                            type="submit"
                            variant="gold"
                            className="mt-1"
                            disabled={processing}
                        >
                            {processing ? t('Bezig…') : t(submitLabel)}
                        </MaisonButton>
                    </>
                )
            }
        </Form>
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
