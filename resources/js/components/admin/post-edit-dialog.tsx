import { useForm } from '@inertiajs/react';
import { Loader2, Pencil } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import communityPostsRoutes from '@/routes/admin/community/posts';

interface PostEditDialogProps {
    postId: string;
    content: string;
}

export function PostEditDialog({ postId, content }: PostEditDialogProps) {
    const { t } = useTranslation();
    const locale = wayfinderLocale();
    const [open, setOpen] = useState(false);

    const form = useForm(
        communityPostsRoutes.update({
            locale,
            communityPost: Number(postId),
        }),
        { content },
    );

    useEffect(() => {
        if (! open) {
            return;
        }

        form.setData('content', content);
    }, [open, postId, content]);

    function submit(event: FormEvent) {
        event.preventDefault();
        form.submit({
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm" type="button">
                    <Pencil className="h-3.5 w-3.5" />
                    {t('Bewerken')}
                </Button>
            </DialogTrigger>
            <DialogContent
                className="admin-kit sm:max-w-lg"
                onOpenAutoFocus={(event) => event.preventDefault()}
            >
                <DialogHeader>
                    <DialogTitle>{t('Bericht bewerken')}</DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        {t(
                            'Wijzig de brontekst van uw bericht. Vertalingen worden automatisch bijgewerkt.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="post-content">{t('Inhoud')}</Label>
                        <Textarea
                            id="post-content"
                            value={form.data.content}
                            onChange={(event) =>
                                form.setData('content', event.target.value)
                            }
                            className="min-h-32 resize-y"
                            maxLength={2000}
                        />
                        <InputError message={form.errors.content} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : null}
                            {t('Opslaan')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
