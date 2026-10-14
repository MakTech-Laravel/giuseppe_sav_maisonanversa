import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import * as sessionRoutes from '@/routes/community/sessions';

const MAX_MESSAGE = 1000;

/**
 * The organiser cancels with an optional note; every player receives it in
 * the cancellation mail and notification.
 */
export function CancelSessionDialog({
    sessionId,
    triggerClassName,
}: {
    sessionId: number;
    triggerClassName: string;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const [open, setOpen] = useState(false);
    const [message, setMessage] = useState('');
    const [processing, setProcessing] = useState(false);

    function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        router.delete(
            sessionRoutes.destroy({ locale, communitySession: sessionId }).url,
            {
                data: { message: message.trim() || null },
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <button type="button" className={triggerClassName}>
                    {t('Sessie annuleren')}
                </button>
            </DialogTrigger>

            <DialogContent className="border-gold/20 bg-cream sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle className="font-serif text-2xl font-medium text-choc">
                        {t('Deze sessie annuleren?')}
                    </DialogTitle>
                    <DialogDescription className="font-sans text-[11px] tracking-[0.1em] text-stone uppercase">
                        {t('Alle spelers krijgen een melding en een e-mail.')}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <label className="flex flex-col gap-1.5">
                        <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                            {t('Bericht aan de spelers (optioneel)')}
                        </span>
                        <textarea
                            value={message}
                            maxLength={MAX_MESSAGE}
                            rows={4}
                            onChange={(event) => setMessage(event.target.value)}
                            className="w-full resize-y border border-gold/20 bg-cream px-4 py-3 font-serif text-base text-choc transition-colors outline-none focus:border-gold2"
                        />
                        <span className="self-end font-sans text-[10px] text-stone tabular-nums">
                            {message.length} / {MAX_MESSAGE}
                        </span>
                    </label>

                    <div className="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="submit"
                            disabled={processing}
                            className="flex-1 cursor-pointer bg-choc px-6 py-4 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 disabled:opacity-50"
                        >
                            {t('Sessie annuleren')}
                        </button>
                        <button
                            type="button"
                            onClick={() => setOpen(false)}
                            className="flex-1 cursor-pointer border border-gold/30 bg-transparent px-6 py-4 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10"
                        >
                            {t('Terug')}
                        </button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
