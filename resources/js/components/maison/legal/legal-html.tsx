import { cn } from '@/lib/utils';
import { sanitizeLegalHtml } from '@/lib/legal-html';

export function LegalHtml({
    html,
    className,
}: {
    html: string;
    className?: string;
}) {
    const sanitized = sanitizeLegalHtml(html);

    if (sanitized === '') {
        return null;
    }

    return (
        <div
            className={cn('legal-prose', className)}
            dangerouslySetInnerHTML={{ __html: sanitized }}
        />
    );
}
