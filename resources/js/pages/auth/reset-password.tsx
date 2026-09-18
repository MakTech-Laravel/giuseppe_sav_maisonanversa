import { Form, Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { email as passwordEmail, update } from '@/routes/password';

type Props = {
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ email, passwordRules }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Wachtwoord opnieuw instellen')} />

            <Form
                {...update.form()}
                className="flex flex-col gap-6"
                resetOnSuccess={['password', 'password_confirmation', 'otp']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">{t('E-mailadres')}</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                defaultValue={email}
                                className="mt-1 block w-full"
                                required
                            />
                            <InputError
                                message={errors.email}
                                className="mt-2"
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="otp">{t('6-cijferige code')}</Label>
                            <Input
                                id="otp"
                                type="text"
                                name="otp"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={6}
                                required
                                autoFocus
                                className="mt-1 block w-full tracking-[0.35em]"
                                placeholder="000000"
                            />
                            <InputError message={errors.otp} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('Nieuw wachtwoord instellen')}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder={t('Nieuw wachtwoord')}
                                passwordrules={passwordRules}
                                required
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                {t('Bevestig nieuw wachtwoord')}
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                autoComplete="new-password"
                                className="mt-1 block w-full"
                                placeholder={t('Bevestig wachtwoord')}
                                passwordrules={passwordRules}
                                required
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full"
                            disabled={processing}
                            data-test="reset-password-button"
                        >
                            {processing && <Spinner />}
                            {t('Wachtwoord opslaan')}
                        </Button>
                    </div>
                )}
            </Form>

            <Form {...passwordEmail.form()} className="mt-4 text-center">
                {({ processing }) => (
                    <>
                        <input type="hidden" name="email" value={email} />
                        <button
                            type="submit"
                            disabled={processing || email === ''}
                            className="font-sans text-[10px] tracking-[0.2em] text-stone uppercase hover:text-gold disabled:opacity-50"
                        >
                            {t('Nieuwe code versturen')}
                        </button>
                    </>
                )}
            </Form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Wachtwoord opnieuw instellen',
    description:
        'Voer de code uit uw e-mail in en stel uw nieuwe wachtwoord in.',
};
