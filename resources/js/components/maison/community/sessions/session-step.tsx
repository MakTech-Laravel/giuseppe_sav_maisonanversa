import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';

type SessionStepProps = {
    index: number;
    title: string;
    description?: string;
    children: ReactNode;
    className?: string;
};

/**
 * One numbered row of the session composer. On desktop every step is stacked
 * on the page; below `md` the create page shows one at a time.
 */
export function SessionStep({
    index,
    title,
    description,
    children,
    className,
}: SessionStepProps) {
    return (
        <section
            className={cn(
                'grid gap-4 border-b border-gold/15 px-6 py-7 last:border-b-0 md:grid-cols-[13rem_1fr] md:gap-8 md:px-8',
                className,
            )}
        >
            <div className="flex gap-3">
                <span
                    aria-hidden="true"
                    className="font-sans text-[11px] tracking-[0.1em] text-gold2"
                >
                    {index}
                </span>
                <div>
                    <h2 className="font-sans text-[11px] font-medium tracking-[0.22em] text-choc uppercase">
                        {title}
                    </h2>
                    {description && (
                        <p className="mt-1 font-sans text-[11px] leading-relaxed text-stone">
                            {description}
                        </p>
                    )}
                </div>
            </div>

            <div className="min-w-0">{children}</div>
        </section>
    );
}

type ChoiceGroupProps<T extends string | number> = {
    options: readonly { value: T; label: string }[];
    value: T;
    onChange: (value: T) => void;
    label: string;
    /** Runs the label through t() — off for values like "90 min". */
    translateLabels?: boolean;
    columns?: string;
};

export function ChoiceGroup<T extends string | number>({
    options,
    value,
    onChange,
    label,
    translateLabels = true,
    columns = 'grid-cols-2 sm:grid-cols-4',
}: ChoiceGroupProps<T>) {
    const { t } = useTranslation();

    return (
        <div role="radiogroup" aria-label={label} className={cn('grid gap-2', columns)}>
            {options.map((option) => {
                const active = option.value === value;

                return (
                    <button
                        key={String(option.value)}
                        type="button"
                        role="radio"
                        aria-checked={active}
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'cursor-pointer border px-2 py-3 text-center font-sans text-[10px] font-medium tracking-[0.08em] break-words whitespace-normal uppercase transition-colors sm:px-3',
                            active
                                ? 'border-choc bg-choc text-cream'
                                : 'border-gold/25 bg-cream text-choc hover:border-gold hover:bg-gold/8',
                        )}
                    >
                        {translateLabels ? t(option.label) : option.label}
                    </button>
                );
            })}
        </div>
    );
}
