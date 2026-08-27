import { CheckIcon } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { cn } from '@/lib/utils';

export type FormStep = {
    id: string;
    label: string;
    description?: string;
};

/**
 * Numbered step rail for the product wizard. Steps ahead of the furthest
 * reached step stay locked so validation can gate forward navigation.
 */
export function FormStepper({
    steps,
    currentIndex,
    furthestIndex,
    onSelect,
}: {
    steps: FormStep[];
    currentIndex: number;
    furthestIndex: number;
    onSelect: (index: number) => void;
}) {
    const { t } = useTranslation();

    return (
        <ol className="flex w-full gap-1 overflow-x-auto scrollbar-none">
            {steps.map((step, index) => {
                const isCurrent = index === currentIndex;
                const isComplete = index < furthestIndex;
                const isLocked = index > furthestIndex;

                return (
                    <li key={step.id} className="min-w-0 flex-1">
                        <button
                            type="button"
                            disabled={isLocked}
                            aria-current={isCurrent ? 'step' : undefined}
                            onClick={() => onSelect(index)}
                            className={cn(
                                'flex w-full items-center gap-2.5 rounded-lg border px-3 py-2.5 text-left transition-colors',
                                isCurrent
                                    ? 'border-primary bg-primary/5'
                                    : 'border-transparent bg-muted/40',
                                isLocked
                                    ? 'cursor-not-allowed opacity-50'
                                    : 'hover:bg-muted',
                            )}
                        >
                            <span
                                className={cn(
                                    'flex size-6 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold',
                                    isComplete
                                        ? 'bg-primary text-primary-foreground'
                                        : isCurrent
                                          ? 'border border-primary text-primary'
                                          : 'border text-muted-foreground',
                                )}
                            >
                                {isComplete ? (
                                    <CheckIcon className="size-3.5" />
                                ) : (
                                    index + 1
                                )}
                            </span>
                            <span className="min-w-0">
                                <span className="block truncate text-xs font-medium">
                                    {t(step.label)}
                                </span>
                                {step.description ? (
                                    <span className="block truncate text-[11px] text-muted-foreground">
                                        {t(step.description)}
                                    </span>
                                ) : null}
                            </span>
                        </button>
                    </li>
                );
            })}
        </ol>
    );
}
