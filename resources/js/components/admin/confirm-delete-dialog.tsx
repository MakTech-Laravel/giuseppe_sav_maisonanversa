import { Loader2 } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';

interface ConfirmDeleteDialogProps {
    title?: string;
    description: ReactNode;
    confirmLabel?: string;
    children: ReactNode;
    onConfirm: () => void;
    processing?: boolean;
}

/**
 * Accessible delete confirmation built on AlertDialog.
 * Wrap the trigger element as `children` (rendered `asChild`).
 */
export function ConfirmDeleteDialog({
    title,
    description,
    confirmLabel,
    children,
    onConfirm,
    processing = false,
}: ConfirmDeleteDialogProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    return (
        <AlertDialog open={open} onOpenChange={setOpen}>
            <AlertDialogTrigger asChild>{children}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {title ?? t('Weet u het absoluut zeker?')}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        {t('Annuleren')}
                    </AlertDialogCancel>
                    <Button
                        variant="destructive"
                        disabled={processing}
                        onClick={(e) => {
                            e.preventDefault();
                            onConfirm();
                            setOpen(false);
                        }}
                    >
                        {processing && (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        )}
                        {confirmLabel ?? t('Verwijderen')}
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
