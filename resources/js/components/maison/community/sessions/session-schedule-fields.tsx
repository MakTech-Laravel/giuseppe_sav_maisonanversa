import { startOfToday } from 'date-fns';
import { CalendarDays, ChevronDown, Clock, Timer } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    formatSessionDateLong,
    parseDateInputValue,
    toDateInputValue,
} from '@/lib/session-format';
import { cn } from '@/lib/utils';
import type { DurationOption } from '@/types/session';

type SessionScheduleFieldsProps = {
    date: string;
    time: string;
    durationMinutes: number;
    durations: DurationOption[];
    locale: string;
    onDateChange: (value: string) => void;
    onTimeChange: (value: string) => void;
    onDurationChange: (value: number) => void;
    error?: string;
};

const HOURS = Array.from({ length: 17 }, (_, index) =>
    String(index + 6).padStart(2, '0'),
);
const MINUTES = Array.from({ length: 60 }, (_, index) =>
    String(index).padStart(2, '0'),
);

const fieldClassName =
    'flex w-full cursor-pointer items-center justify-between gap-3 border border-gold/20 bg-cream px-4 py-3 text-left font-serif text-base text-choc outline-none transition-colors hover:border-gold/40 focus:border-gold2';

export function SessionScheduleFields({
    date,
    time,
    durationMinutes,
    durations,
    locale,
    onDateChange,
    onTimeChange,
    onDurationChange,
    error,
}: SessionScheduleFieldsProps) {
    const { t } = useTranslation();
    const [dateOpen, setDateOpen] = useState(false);
    const [timeOpen, setTimeOpen] = useState(false);

    const selectedDate = useMemo(() => parseDateInputValue(date), [date]);
    const [hour = '10', minute = '30'] = time.split(':');

    return (
        <div className="space-y-3">
            <div className="grid gap-3 sm:grid-cols-3">
                <label className="flex flex-col gap-1.5">
                    <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                        {t('Datum')}
                    </span>
                    <Popover open={dateOpen} onOpenChange={setDateOpen}>
                        <PopoverTrigger asChild>
                            <button
                                type="button"
                                aria-label={t('Datum')}
                                className={fieldClassName}
                            >
                                <span className="inline-flex min-w-0 items-center gap-2">
                                    <CalendarDays
                                        className="size-4 shrink-0 text-stone"
                                        aria-hidden="true"
                                    />
                                    <span className="truncate">
                                        {selectedDate
                                            ? formatSessionDateLong(
                                                  `${date}T12:00:00`,
                                                  locale,
                                              )
                                            : t('Kies een datum')}
                                    </span>
                                </span>
                                <ChevronDown
                                    className="size-4 shrink-0 text-stone"
                                    aria-hidden="true"
                                />
                            </button>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto">
                            <Calendar
                                mode="single"
                                selected={selectedDate}
                                onSelect={(next) => {
                                    if (!next) {
                                        return;
                                    }

                                    onDateChange(toDateInputValue(next));
                                    setDateOpen(false);
                                }}
                                disabled={{ before: startOfToday() }}
                            />
                        </PopoverContent>
                    </Popover>
                </label>

                <label className="flex flex-col gap-1.5">
                    <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                        {t('Tijdstip')}
                    </span>
                    <Popover open={timeOpen} onOpenChange={setTimeOpen}>
                        <PopoverTrigger asChild>
                            <button
                                type="button"
                                aria-label={t('Tijdstip')}
                                className={fieldClassName}
                            >
                                <span className="inline-flex items-center gap-2">
                                    <Clock
                                        className="size-4 shrink-0 text-stone"
                                        aria-hidden="true"
                                    />
                                    {time || t('Kies een tijdstip')}
                                </span>
                                <ChevronDown
                                    className="size-4 shrink-0 text-stone"
                                    aria-hidden="true"
                                />
                            </button>
                        </PopoverTrigger>
                        <PopoverContent className="w-56 p-3">
                            <div className="grid grid-cols-2 gap-3">
                                <TimeColumn
                                    label={t('Uur')}
                                    values={HOURS}
                                    selected={hour}
                                    onSelect={(nextHour) =>
                                        onTimeChange(`${nextHour}:${minute}`)
                                    }
                                />
                                <TimeColumn
                                    label={t('Minuten')}
                                    values={MINUTES}
                                    selected={minute}
                                    onSelect={(nextMinute) => {
                                        onTimeChange(`${hour}:${nextMinute}`);
                                        setTimeOpen(false);
                                    }}
                                />
                            </div>
                        </PopoverContent>
                    </Popover>
                </label>

                <label className="flex flex-col gap-1.5">
                    <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                        {t('Duur')}
                    </span>
                    <Select
                        value={String(durationMinutes)}
                        onValueChange={(value) =>
                            onDurationChange(Number(value))
                        }
                    >
                        <SelectTrigger
                            aria-label={t('Duur')}
                            className={cn(
                                fieldClassName,
                                'h-auto rounded-none shadow-none focus-visible:ring-0 data-[size=default]:h-auto',
                            )}
                        >
                            <span className="inline-flex items-center gap-2">
                                <Timer
                                    className="size-4 shrink-0 text-stone"
                                    aria-hidden="true"
                                />
                                <SelectValue />
                            </span>
                        </SelectTrigger>
                        <SelectContent className="rounded-none border-gold/20 bg-cream">
                            {durations.map((duration) => (
                                <SelectItem
                                    key={duration.value}
                                    value={String(duration.value)}
                                >
                                    {duration.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </label>
            </div>

            {error && (
                <p className="font-sans text-[11px] text-red-800">{error}</p>
            )}
        </div>
    );
}

function TimeColumn({
    label,
    values,
    selected,
    onSelect,
}: {
    label: string;
    values: string[];
    selected: string;
    onSelect: (value: string) => void;
}) {
    return (
        <div>
            <p className="mb-1.5 font-sans text-[9px] tracking-[0.18em] text-stone uppercase">
                {label}
            </p>
            <ScrollArea className="h-48 border border-gold/15">
                <div className="flex flex-col p-1">
                    {values.map((value) => (
                        <button
                            key={value}
                            type="button"
                            onClick={() => onSelect(value)}
                            className={cn(
                                'cursor-pointer px-3 py-1.5 text-left font-serif text-sm transition-colors',
                                value === selected
                                    ? 'bg-choc text-cream'
                                    : 'text-choc hover:bg-gold/15',
                            )}
                        >
                            {value}
                        </button>
                    ))}
                </div>
            </ScrollArea>
        </div>
    );
}
