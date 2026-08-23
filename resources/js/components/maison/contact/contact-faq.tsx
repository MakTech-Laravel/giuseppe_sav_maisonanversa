import { cn } from '@/lib/utils';

type ContactFaqProps = {
    className?: string;
    faqs: Array<{ question: string; answer: string }>;
};

/**
 * Native `<details>` FAQ for the contact bureau.
 *
 * Native `<details>` FAQ for the contact bureau on a dark ground, with keyboard
 * support and `aria-expanded` without a Radix wrapper.
 */
export function ContactFaq({ className, faqs }: ContactFaqProps) {
    if (faqs.length === 0) {
        return null;
    }

    return (
        <div className={cn('mt-1', className)}>
            {faqs.map((item) => (
                <details
                    key={item.question}
                    className="group border-b border-gold/14"
                >
                    <summary className="flex cursor-pointer list-none items-center justify-between gap-4 py-4 font-sans text-[13px] text-cream [&::-webkit-details-marker]:hidden">
                        {item.question}
                        <span
                            aria-hidden="true"
                            className="text-lg text-gold group-open:hidden"
                        >
                            +
                        </span>
                        <span
                            aria-hidden="true"
                            className="hidden text-lg text-gold group-open:inline"
                        >
                            −
                        </span>
                    </summary>

                    <div className="pb-4.5 font-sans text-[13px] leading-[1.8] text-sand">
                        {item.answer}
                    </div>
                </details>
            ))}
        </div>
    );
}
