import type { KeyboardEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { prettyPrintLegalHtml } from '@/lib/legal-html';

export function LegalHtmlSourceEditor({
    value,
    onChange,
    error,
}: {
    value: string;
    onChange: (value: string) => void;
    error?: string | null;
}) {
    const { t } = useTranslation();
    const lines = Math.max(12, value.split('\n').length);

    function handleKeyDown(event: KeyboardEvent<HTMLTextAreaElement>) {
        if (event.key !== 'Tab') {
            return;
        }

        event.preventDefault();
        const target = event.currentTarget;
        const start = target.selectionStart;
        const end = target.selectionEnd;
        const next = `${value.slice(0, start)}\t${value.slice(end)}`;
        onChange(next);
        requestAnimationFrame(() => {
            target.selectionStart = start + 1;
            target.selectionEnd = start + 1;
        });
    }

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between gap-3">
                <p className="font-mono text-[11px] tracking-wide text-muted-foreground uppercase">
                    {t('{{count}} regels', { count: lines })}
                </p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => onChange(prettyPrintLegalHtml(value))}
                >
                    {t('Opmaken')}
                </Button>
            </div>
            <Textarea
                value={value}
                onChange={(event) => onChange(event.target.value)}
                onKeyDown={handleKeyDown}
                spellCheck={false}
                className="min-h-88 resize-y font-mono text-[13px] leading-relaxed"
                aria-invalid={Boolean(error)}
            />
            {error ? (
                <p className="text-sm text-destructive">{error}</p>
            ) : null}
        </div>
    );
}
