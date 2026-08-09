import { Plus } from 'lucide-react';
import { Accordion as AccordionPrimitive } from 'radix-ui';
import type { ComponentProps, ReactNode } from 'react';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
} from '@/components/ui/accordion';
import { cn } from '@/lib/utils';

/**
 * The house's disclosure list, used for the product and contact FAQs.
 *
 * Built on the Radix accordion so arrow keys, Home/End and the
 * `aria-expanded`/`aria-controls` pairing come for free — the prototype
 * toggled a class on a div and animated `max-height`, which meant no keyboard
 * support and a collapsed panel that was still in the tab order.
 *
 * The trigger is defined here rather than reused from the shadcn one because
 * the house mark is a plus that rotates into a cross, not a chevron.
 */
function MaisonAccordionTrigger({
    className,
    children,
    ...props
}: ComponentProps<typeof AccordionPrimitive.Trigger>) {
    return (
        <AccordionPrimitive.Header className="flex">
            <AccordionPrimitive.Trigger
                data-slot="accordion-trigger"
                className={cn(
                    'flex flex-1 items-center justify-between gap-6 py-6.5 text-left font-serif text-lg text-choc transition-colors outline-none hover:text-gold2',
                    '[&[data-state=open]>svg]:rotate-45',
                    className,
                )}
                {...props}
            >
                {children}

                <Plus
                    aria-hidden="true"
                    className="size-5.5 shrink-0 text-gold2 transition-transform duration-300"
                />
            </AccordionPrimitive.Trigger>
        </AccordionPrimitive.Header>
    );
}

export type MaisonAccordionEntry = {
    /** Stable value for the item, so an open panel survives a re-render. */
    value: string;
    question: ReactNode;
    answer: ReactNode;
};

type MaisonAccordionProps = {
    entries: readonly MaisonAccordionEntry[];
    className?: string;
};

export function MaisonAccordion({ entries, className }: MaisonAccordionProps) {
    return (
        <Accordion type="single" collapsible className={className}>
            {entries.map((entry) => (
                <AccordionItem
                    key={entry.value}
                    value={entry.value}
                    className="border-b border-gold/20 last:border-b-0"
                >
                    <MaisonAccordionTrigger>
                        {entry.question}
                    </MaisonAccordionTrigger>

                    <AccordionContent className="pb-6.5 font-sans text-sm leading-[1.8] text-choc3">
                        {entry.answer}
                    </AccordionContent>
                </AccordionItem>
            ))}
        </Accordion>
    );
}
