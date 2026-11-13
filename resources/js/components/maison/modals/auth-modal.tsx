import { Form } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { GenderSelect } from '@/components/gender-select';
import InputError from '@/components/input-error';
import {
    MaisonModal,
    modalInputClassName,
    modalNoteClassName,
} from '@/components/maison/modals/maison-modal';
import { ModalPasswordInput } from '@/components/maison/modals/modal-password-input';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { useRegistrationEnabled } from '@/hooks/use-registration-enabled';
import { cn } from '@/lib/utils';
import { store as loginStore } from '@/routes/login';
import { email as passwordEmail, update as passwordUpdate } from '@/routes/password';
import { store as registerStore } from '@/routes/register';
import { store as twoFactorStore } from '@/routes/two-factor/login';

export type AuthView = 'login' | 'register' | 'forgot' | 'two-factor';

type AuthModalProps = {
    initialView?: AuthView;
    onClose: () => void;
    onSwitchView: (view: AuthView) => void;
};

type ViewCopy = {
    title: string;
    subtitle: string;
    description: string;
};

export function AuthModal({
    initialView = 'login',
    onClose,
    onSwitchView,
}: AuthModalProps) {
    const { t } = useTranslation();
    const registrationEnabled = useRegistrationEnabled();
    const [view, setView] = useState<AuthView>(initialView);
    const [forgotSent, setForgotSent] = useState(false);
    const [forgotEmail, setForgotEmail] = useState('');
    const [showRecoveryInput, setShowRecoveryInput] = useState(false);

    useEffect(() => {
        const next =
            initialView === 'register' && !registrationEnabled
                ? 'login'
                : initialView;

        setView(next);

        if (next !== 'forgot') {
            setForgotSent(false);
        }
    }, [initialView, registrationEnabled]);

    useEffect(() => {
        if (view === 'register' && !registrationEnabled) {
            setView('login');
            onSwitchView('login');
        }
    }, [view, registrationEnabled, onSwitchView]);

    const copy = useMemo<ViewCopy>(() => {
        switch (view) {
            case 'register':
                return {
                    title: t('Register'),
                    subtitle: t('Maison Anversa · Become a member'),
                    description: t(
                        'Create your account and access your personal dashboard, Heritage Letter and Founding Circle.',
                    ),
                };
            case 'forgot':
                return {
                    title: t('Forgot password'),
                    subtitle: t('Recovery · Secure and discreet'),
                    description: forgotSent
                        ? t(
                              'Enter the code from your email and choose a new password.',
                          )
                        : t(
                              'Enter your email address and we will send you a one-time code to reset your password.',
                          ),
                };
            case 'two-factor':
                return {
                    title: t('Two-factor authentication'),
                    subtitle: t('Security · Extra verification'),
                    description: showRecoveryInput
                        ? t(
                              'Enter one of your recovery codes to continue.',
                          )
                        : t(
                              'Enter the code from your authenticator app to continue.',
                          ),
                };
            default:
                return {
                    title: t('Log in'),
                    subtitle: t('Heritage Letter · Become a member · Dashboard'),
                    description: t(
                        'Log in with your username or email address and password to access your Maison Anversa account.',
                    ),
                };
        }
    }, [forgotSent, showRecoveryInput, t, view]);

    function switchView(next: AuthView): void {
        const resolved =
            next === 'register' && !registrationEnabled ? 'login' : next;

        setView(resolved);
        setForgotSent(false);
        setForgotEmail('');
        setShowRecoveryInput(false);
        onSwitchView(resolved);
    }

    return (
        <MaisonModal label={copy.title} onClose={onClose}>
            <h2 className="mb-1.5 font-serif text-[32px] font-medium text-choc">
                {copy.title}
            </h2>
            <span className="mb-6 block font-sans text-[9px] tracking-[0.25em] text-gold2 uppercase">
                {copy.subtitle}
            </span>

            {view === 'login' && (
                <>
                    <p className="mb-7 text-[15px] leading-[1.8] text-choc3">
                        {copy.description}
                    </p>

                    <Form
                        {...loginStore.form()}
                        resetOnSuccess={['password']}
                        className="flex flex-col gap-3.5"
                    >
                        {({ processing, errors }) => (
                            <>
                                <label
                                    className="sr-only"
                                    htmlFor="auth-login"
                                >
                                    {t('Username / Email')}
                                </label>
                                <input
                                    id="auth-login"
                                    type="text"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="username"
                                    placeholder={t(
                                        'Username / Email',
                                    )}
                                    className={modalInputClassName}
                                />
                                <InputError message={errors.email} />

                                <label
                                    className="sr-only"
                                    htmlFor="auth-password"
                                >
                                    {t('Password')}
                                </label>
                                <ModalPasswordInput
                                    id="auth-password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    placeholder={t('Password')}
                                />
                                <InputError message={errors.password} />

                                <label className="flex items-center gap-2 text-[13px] text-choc3">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        className="size-4 accent-choc"
                                    />
                                    {t('Remember me')}
                                </label>

                                <MaisonButton
                                    type="submit"
                                    variant="filled"
                                    block
                                    disabled={processing}
                                >
                                    {t('Log in')}
                                </MaisonButton>
                            </>
                        )}
                    </Form>

                    <div className="mt-5 flex flex-col gap-2 text-center text-[13px] text-choc3">
                        <button
                            type="button"
                            className="font-sans tracking-[0.12em] uppercase transition-colors hover:text-choc"
                            onClick={() => switchView('forgot')}
                        >
                            {t('Forgot password?')}
                        </button>
                        {registrationEnabled && (
                            <button
                                type="button"
                                className="font-sans tracking-[0.12em] uppercase transition-colors hover:text-choc"
                                onClick={() => switchView('register')}
                            >
                                {t('No account yet? Register')}
                            </button>
                        )}
                    </div>
                </>
            )}

            {view === 'register' && registrationEnabled && (
                <>
                    <p className="mb-7 text-[15px] leading-[1.8] text-choc3">
                        {copy.description}
                    </p>

                    <Form
                        {...registerStore.form()}
                        resetOnSuccess={['password', 'password_confirmation']}
                        className="flex flex-col gap-3.5"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="text"
                                    name="name"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                    placeholder={t('Your name')}
                                    className={modalInputClassName}
                                />
                                <InputError message={errors.name} />

                                <input
                                    type="email"
                                    name="email"
                                    required
                                    autoComplete="email"
                                    placeholder={t('Your email address')}
                                    className={modalInputClassName}
                                />
                                <InputError message={errors.email} />

                                <ModalPasswordInput
                                    name="password"
                                    required
                                    autoComplete="new-password"
                                    placeholder={t('Password')}
                                />
                                <InputError message={errors.password} />

                                <ModalPasswordInput
                                    name="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                    placeholder={t('Confirm password')}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />

                                <GenderSelect
                                    aria-label={t('Gender')}
                                    triggerClassName={cn(
                                        modalInputClassName,
                                        'h-auto rounded-none shadow-none data-[size=default]:h-auto [&_svg]:text-choc3',
                                    )}
                                />
                                <InputError message={errors.gender} />

                                <MaisonButton
                                    type="submit"
                                    variant="filled"
                                    block
                                    disabled={processing}
                                >
                                    {t('Register')}
                                </MaisonButton>
                            </>
                        )}
                    </Form>

                    <div className="mt-5 text-center">
                        <button
                            type="button"
                            className="font-sans text-[13px] tracking-[0.12em] text-choc3 uppercase transition-colors hover:text-choc"
                            onClick={() => switchView('login')}
                        >
                            {t('Already have an account? Log in')}
                        </button>
                    </div>
                </>
            )}

            {view === 'forgot' && (
                <>
                    <p className="mb-7 text-[15px] leading-[1.8] text-choc3">
                        {copy.description}
                    </p>

                    {!forgotSent ? (
                        <Form
                            {...passwordEmail.form()}
                            onSuccess={() => setForgotSent(true)}
                            className="flex flex-col gap-3.5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        autoComplete="email"
                                        placeholder={t('Your email address')}
                                        className={modalInputClassName}
                                        value={forgotEmail}
                                        onChange={(event) =>
                                            setForgotEmail(event.target.value)
                                        }
                                    />
                                    <InputError message={errors.email} />

                                    <MaisonButton
                                        type="submit"
                                        variant="filled"
                                        block
                                        disabled={processing}
                                    >
                                        {t('Send code')}
                                    </MaisonButton>
                                </>
                            )}
                        </Form>
                    ) : (
                        <Form
                            {...passwordUpdate.form()}
                            onSuccess={onClose}
                            className="flex flex-col gap-3.5"
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <input
                                        type="hidden"
                                        name="email"
                                        value={forgotEmail}
                                    />

                                    <div className="flex flex-col gap-1.5">
                                        <label
                                            htmlFor="auth-reset-email"
                                            className="font-sans text-[10px] tracking-[0.18em] text-stone uppercase"
                                        >
                                            {t('Email address')}
                                        </label>
                                        <input
                                            id="auth-reset-email"
                                            type="email"
                                            value={forgotEmail}
                                            readOnly
                                            className={cn(
                                                modalInputClassName,
                                                'opacity-70',
                                            )}
                                        />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label
                                            htmlFor="auth-reset-otp"
                                            className="font-sans text-[10px] tracking-[0.18em] text-stone uppercase"
                                        >
                                            {t('6-digit code')}
                                        </label>
                                        <input
                                            id="auth-reset-otp"
                                            type="text"
                                            name="otp"
                                            required
                                            autoFocus
                                            inputMode="numeric"
                                            autoComplete="one-time-code"
                                            maxLength={6}
                                            placeholder="000000"
                                            className={cn(
                                                modalInputClassName,
                                                'tracking-[0.35em]',
                                            )}
                                        />
                                        <InputError message={errors.otp} />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label
                                            htmlFor="auth-reset-password"
                                            className="font-sans text-[10px] tracking-[0.18em] text-stone uppercase"
                                        >
                                            {t('Set new password')}
                                        </label>
                                        <ModalPasswordInput
                                            id="auth-reset-password"
                                            name="password"
                                            required
                                            autoComplete="new-password"
                                            placeholder={t('New password')}
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label
                                            htmlFor="auth-reset-password-confirm"
                                            className="font-sans text-[10px] tracking-[0.18em] text-stone uppercase"
                                        >
                                            {t('Confirm new password')}
                                        </label>
                                        <ModalPasswordInput
                                            id="auth-reset-password-confirm"
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                            placeholder={t(
                                                'Confirm password',
                                            )}
                                        />
                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>

                                    <MaisonButton
                                        type="submit"
                                        variant="filled"
                                        block
                                        disabled={processing}
                                    >
                                        {t('Save password')}
                                    </MaisonButton>

                                    <button
                                        type="button"
                                        className="font-sans text-[11px] tracking-[0.12em] text-stone uppercase transition-colors hover:text-choc"
                                        onClick={() => {
                                            setForgotSent(false);
                                        }}
                                    >
                                        {t('Request another code')}
                                    </button>
                                </>
                            )}
                        </Form>
                    )}

                    <div className="mt-5 text-center">
                        <button
                            type="button"
                            className="font-sans text-[13px] tracking-[0.12em] text-choc3 uppercase transition-colors hover:text-choc"
                            onClick={() => switchView('login')}
                        >
                            {t('Back to log in')}
                        </button>
                    </div>
                </>
            )}

            {view === 'two-factor' && (
                <>
                    <p className="mb-7 text-[15px] leading-[1.8] text-choc3">
                        {copy.description}
                    </p>

                    <Form
                        {...twoFactorStore.form()}
                        resetOnError
                        resetOnSuccess={!showRecoveryInput}
                        className="flex flex-col gap-3.5"
                    >
                        {({ processing, errors, clearErrors }) => (
                            <>
                                {showRecoveryInput ? (
                                    <>
                                        <input
                                            name="recovery_code"
                                            type="text"
                                            required
                                            autoFocus
                                            placeholder={t('Recovery code')}
                                            className={modalInputClassName}
                                        />
                                        <InputError
                                            message={errors.recovery_code}
                                        />
                                    </>
                                ) : (
                                    <>
                                        <input
                                            name="code"
                                            type="text"
                                            inputMode="numeric"
                                            required
                                            autoFocus
                                            placeholder={t('Authentication code')}
                                            className={modalInputClassName}
                                        />
                                        <InputError message={errors.code} />
                                    </>
                                )}

                                <MaisonButton
                                    type="submit"
                                    variant="filled"
                                    block
                                    disabled={processing}
                                >
                                    {t('Continue')}
                                </MaisonButton>

                                <button
                                    type="button"
                                    className="font-sans text-[13px] tracking-[0.12em] text-choc3 uppercase transition-colors hover:text-choc"
                                    onClick={() => {
                                        setShowRecoveryInput(
                                            !showRecoveryInput,
                                        );
                                        clearErrors();
                                    }}
                                >
                                    {showRecoveryInput
                                        ? t('Log in with authentication code')
                                        : t('Log in with recovery code')}
                                </button>
                            </>
                        )}
                    </Form>
                </>
            )}

            <p className={modalNoteClassName}>
                {t(
                    'Your data is handled securely. Only what is needed for your Maison Anversa account.',
                )}
            </p>
        </MaisonModal>
    );
}
