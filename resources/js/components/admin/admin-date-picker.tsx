import { CalendarDays, ChevronDown } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useLocale } from '@/hooks/use-locale';
import {
    parseDateInputValue,
    toDateInputValue,
} from '@/lib/session-format';
import { cn } from '@/lib/utils';

type AdminDatePickerProps = {
    id?: string;
    name?: string;
    value: string;
    onChange: (value: string) => void;
    required?: boolean;
    disabled?: boolean;
    placeholder?: string;
    className?: string;
    /** Disable calendar days before today. */
    disablePast?: boolean;
};

function formatDisplayDate(value: string, locale: string): string {
    const date = parseDateInputValue(value);

    if (!date) {
        return value;
    }

    return new Intl.DateTimeFormat(locale, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

/**
 * Admin-facing date field: shadcn Calendar in a Popover, stores YYYY-MM-DD.
 * Optional `name` renders a hidden input for plain HTML Form posts.
 */
export function AdminDatePicker({
    id,
    name,
    value,
    onChange,
    required = false,
    disabled = false,
    placeholder,
    className,
    disablePast = false,
}: AdminDatePickerProps) {
    const { t } = useTranslation();
    const { locale } = useLocale();
    const [open, setOpen] = useState(false);
    const selected = useMemo(() => parseDateInputValue(value), [value]);

    return (
        <div className={cn('w-full min-w-[12rem]', className)}>
            {name ? (
                <input
                    type="hidden"
                    name={name}
                    value={value}
                    required={required}
                />
            ) : null}
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        disabled={disabled}
                        aria-required={required || undefined}
                        className={cn(
                            'h-9 w-full justify-between px-3 font-normal',
                            !selected && 'text-muted-foreground',
                        )}
                    >
                        <span className="inline-flex min-w-0 items-center gap-2">
                            <CalendarDays
                                className="size-4 shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <span className="truncate">
                                {selected
                                    ? formatDisplayDate(value, locale)
                                    : (placeholder ?? t('Kies een datum'))}
                            </span>
                        </span>
                        <ChevronDown
                            className="size-4 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    align="start"
                    className="w-auto border-border bg-popover p-0 text-popover-foreground shadow-md"
                >
                    <Calendar
                        mode="single"
                        selected={selected}
                        onSelect={(next) => {
                            if (!next) {
                                return;
                            }

                            onChange(toDateInputValue(next));
                            setOpen(false);
                        }}
                        disabled={
                            disablePast
                                ? { before: new Date(new Date().setHours(0, 0, 0, 0)) }
                                : undefined
                        }
                        className="bg-popover text-popover-foreground"
                        classNames={{
                            month_caption:
                                'flex h-9 items-center justify-center text-sm font-medium text-foreground',
                            weekday:
                                'flex-1 py-1 text-center text-[10px] font-medium tracking-wide text-muted-foreground uppercase',
                            day_button:
                                'size-9 cursor-pointer rounded-md text-sm text-foreground transition-colors hover:bg-accent hover:text-accent-foreground',
                            selected:
                                '[&_button]:bg-primary [&_button]:text-primary-foreground [&_button]:hover:bg-primary [&_button]:hover:text-primary-foreground',
                            today: 'font-medium ring-1 ring-primary/40 ring-inset',
                            outside: '[&_button]:text-muted-foreground/50',
                            disabled:
                                'pointer-events-none [&_button]:text-muted-foreground/30',
                            button_previous:
                                'inline-flex size-8 cursor-pointer items-center justify-center rounded-md text-foreground transition-colors hover:bg-accent disabled:opacity-30',
                            button_next:
                                'inline-flex size-8 cursor-pointer items-center justify-center rounded-md text-foreground transition-colors hover:bg-accent disabled:opacity-30',
                        }}
                    />
                </PopoverContent>
            </Popover>
        </div>
    );
}
