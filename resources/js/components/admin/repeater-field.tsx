import { ArrowDownIcon, ArrowUpIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * Add / remove / reorder rows around a render-prop body. Ordering uses
 * up-down buttons so no drag-and-drop dependency is needed.
 */
export function RepeaterField<T>({
    rows,
    onChange,
    makeRow,
    renderRow,
    rowKey,
    addLabel,
    emptyLabel,
    maxRows,
    className,
}: {
    rows: T[];
    onChange: (rows: T[]) => void;
    makeRow: () => T;
    renderRow: (
        row: T,
        index: number,
        update: (patch: Partial<T>) => void,
    ) => ReactNode;
    rowKey: (row: T, index: number) => string;
    addLabel: string;
    emptyLabel?: string;
    maxRows?: number;
    className?: string;
}) {
    const { t } = useTranslation();
    const canAdd = maxRows === undefined || rows.length < maxRows;

    const move = (from: number, to: number) => {
        if (to < 0 || to >= rows.length) {
            return;
        }

        const next = [...rows];
        const [moved] = next.splice(from, 1);
        next.splice(to, 0, moved);
        onChange(next);
    };

    return (
        <div className={cn('space-y-3', className)}>
            {rows.length === 0 && emptyLabel ? (
                <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                    {t(emptyLabel)}
                </p>
            ) : null}

            {rows.map((row, index) => (
                <div
                    key={rowKey(row, index)}
                    className="rounded-lg border bg-background p-3 sm:p-4"
                >
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <span className="text-xs font-medium text-muted-foreground">
                            {t('Rij {{number}}', { number: index + 1 })}
                        </span>
                        <div className="flex items-center gap-1">
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="size-8"
                                disabled={index === 0}
                                aria-label={t('Omhoog verplaatsen')}
                                onClick={() => move(index, index - 1)}
                            >
                                <ArrowUpIcon className="size-4" />
                            </Button>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="size-8"
                                disabled={index === rows.length - 1}
                                aria-label={t('Omlaag verplaatsen')}
                                onClick={() => move(index, index + 1)}
                            >
                                <ArrowDownIcon className="size-4" />
                            </Button>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="size-8 text-destructive hover:text-destructive"
                                aria-label={t('Rij verwijderen')}
                                onClick={() =>
                                    onChange(
                                        rows.filter((_, at) => at !== index),
                                    )
                                }
                            >
                                <Trash2Icon className="size-4" />
                            </Button>
                        </div>
                    </div>

                    {renderRow(row, index, (patch) =>
                        onChange(
                            rows.map((current, at) =>
                                at === index
                                    ? { ...current, ...patch }
                                    : current,
                            ),
                        ),
                    )}
                </div>
            ))}

            <Button
                type="button"
                size="sm"
                variant="outline"
                disabled={!canAdd}
                onClick={() => onChange([...rows, makeRow()])}
            >
                <PlusIcon className="size-4" />
                {t(addLabel)}
            </Button>
        </div>
    );
}
