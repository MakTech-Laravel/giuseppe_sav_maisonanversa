import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import * as React from 'react';
import { DayPicker } from 'react-day-picker';

import { cn } from '@/lib/utils';

function Calendar({
    className,
    classNames,
    showOutsideDays = true,
    ...props
}: React.ComponentProps<typeof DayPicker>) {
    return (
        <DayPicker
            showOutsideDays={showOutsideDays}
            className={cn('bg-cream p-3', className)}
            classNames={{
                months: 'relative flex flex-col',
                month: 'flex w-full flex-col gap-3',
                month_caption:
                    'flex h-9 items-center justify-center font-sans text-[11px] tracking-[0.16em] text-choc uppercase',
                caption_label: 'font-medium',
                nav: 'absolute inset-x-0 top-0 flex items-center justify-between',
                button_previous: navButtonClassName,
                button_next: navButtonClassName,
                month_grid: 'w-full border-collapse',
                weekdays: 'flex',
                weekday:
                    'flex-1 py-1 text-center font-sans text-[9px] tracking-[0.14em] text-stone uppercase',
                week: 'mt-1 flex w-full',
                day: 'relative size-9 p-0 text-center',
                day_button: cn(
                    'size-9 cursor-pointer font-serif text-sm text-choc transition-colors hover:bg-gold/15',
                    'aria-selected:bg-choc aria-selected:text-cream',
                ),
                selected: 'bg-choc text-cream',
                today: 'font-medium ring-1 ring-gold/40 ring-inset',
                outside: 'text-stone/50',
                disabled: 'pointer-events-none text-stone/30',
                hidden: 'invisible',
                ...classNames,
            }}
            components={{
                Chevron: ({ orientation, className: chevronClass }) =>
                    orientation === 'left' ? (
                        <ChevronLeftIcon
                            className={cn('size-4', chevronClass)}
                        />
                    ) : (
                        <ChevronRightIcon
                            className={cn('size-4', chevronClass)}
                        />
                    ),
            }}
            {...props}
        />
    );
}

const navButtonClassName =
    'inline-flex size-8 cursor-pointer items-center justify-center text-choc transition-colors hover:bg-gold/15 disabled:opacity-30';

export { Calendar };
