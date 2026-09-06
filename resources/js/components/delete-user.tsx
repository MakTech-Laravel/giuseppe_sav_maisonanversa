import { Form } from '@inertiajs/react';
import { useRef } from 'react';
import { useTranslation } from 'react-i18next';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';

export default function DeleteUser() {
    const { t } = useTranslation();
    const passwordInput = useRef<HTMLInputElement>(null);

    return (
        <section className="rounded-lg border border-destructive/35 bg-destructive/10 p-5 sm:p-6">
            <header className="mb-4 space-y-1">
                <h2 className="text-base font-medium tracking-tight text-foreground">
                    {t('Account verwijderen')}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {t(
                        'Verwijder uw account en al zijn gegevens permanent. Dit kan niet ongedaan worden gemaakt.',
                    )}
                </p>
            </header>

            <div className="rounded-md border border-destructive/30 bg-background/40 px-4 py-3 text-sm">
                <p className="font-medium text-destructive">
                    {t('Waarschuwing')}
                </p>
                <p className="mt-0.5 text-muted-foreground">
                    {t('Ga voorzichtig te werk — deze actie is permanent.')}
                </p>
            </div>

            <div className="mt-4">
                <Dialog>
                    <DialogTrigger asChild>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                        >
                            {t('Account verwijderen')}
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>
                            {t(
                                'Weet u zeker dat u uw account wilt verwijderen?',
                            )}
                        </DialogTitle>
                        <DialogDescription>
                            {t(
                                'Zodra uw account is verwijderd, worden al zijn gegevens permanent gewist. Voer uw wachtwoord in om te bevestigen dat u uw account definitief wilt verwijderen.',
                            )}
                        </DialogDescription>

                        <Form
                            {...ProfileController.destroy.form(
                                wayfinderLocale(),
                            )}
                            options={{
                                preserveScroll: true,
                            }}
                            onError={() => passwordInput.current?.focus()}
                            resetOnSuccess
                            className="space-y-6"
                        >
                            {({ resetAndClearErrors, processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password"
                                            className="sr-only"
                                        >
                                            {t('Wachtwoord')}
                                        </Label>

                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            ref={passwordInput}
                                            placeholder={t('Wachtwoord')}
                                            autoComplete="current-password"
                                        />

                                        <InputError message={errors.password} />
                                    </div>

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button
                                                variant="secondary"
                                                onClick={() =>
                                                    resetAndClearErrors()
                                                }
                                            >
                                                {t('Annuleren')}
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                            asChild
                                        >
                                            <button
                                                type="submit"
                                                data-test="confirm-delete-user-button"
                                            >
                                                {t('Account verwijderen')}
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </section>
    );
}
