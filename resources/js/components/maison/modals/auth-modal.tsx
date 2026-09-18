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
    const [view, setView] = useState<AuthView>(initialView);
    const [forgotSent, setForgotSent] = useState(false);
    const [forgotEmail, setForgotEmail] = useState('');
    const [showRecoveryInput, setShowRecoveryInput] = useState(false);

    useEffect(() => {
        setView(initialView);

        if (initialView !== 'forgot') {
            setForgotSent(false);
        }
    }, [initialView]);

    const copy = useMemo<ViewCopy>(() => {
        switch (view) {
            case 'register':
                return {
                    title: t('Registreren'),
                    subtitle: t('Maison Anversa · Lid worden'),
                    description: t(
                        'Maak uw account aan en krijg toegang tot uw persoonlijke dashboard, Heritage Letter en Founding Circle.',
                    ),
                };
            case 'forgot':
                return {
                    title: t('Wachtwoord vergeten'),
                    subtitle: t('Herstel · Veilig en discreet'),
                    description: forgotSent
                        ? t(
                              'Voer de code uit uw e-mail in en kies een nieuw wachtwoord.',
                          )
                        : t(
                              'Vul uw e-mailadres in en wij sturen u een eenmalige code om uw wachtwoord opnieuw in te stellen.',
                          ),
                };
            case 'two-factor':
                return {
                    title: t('Tweestapsverificatie'),
                    subtitle: t('Beveiliging · Extra verificatie'),
                    description: showRecoveryInput
                        ? t(
                              'Voer een van uw herstelcodes in om verder te gaan.',
                          )
                        : t(
                              'Voer de code in van uw authenticator-app om verder te gaan.',
                          ),
                };
            default:
                return {
                    title: t('Inloggen'),
                    subtitle: t('Heritage Letter · Lid worden · Dashboard'),
                    description: t(
                        'Log in met uw gebruikersnaam of e-mailadres en wachtwoord om toegang te krijgen tot uw Maison Anversa-account.',
                    ),
                };
        }
    }, [forgotSent, showRecoveryInput, t, view]);

    function switchView(next: AuthView): void {
        setView(next);
        setForgotSent(false);
        setForgotEmail('');
        setShowRecoveryInput(false);
        onSwitchView(next);
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
                                    {t('Gebruikersnaam / E-mail')}
                                </label>
                                <input
                                    id="auth-login"
                                    type="text"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="username"
                                    placeholder={t(
                                        'Gebruikersnaam / E-mail',
                                    )}
                                    className={modalInputClassName}
                                />
                                <InputError message={errors.email} />

                                <label
                                    className="sr-only"
                                    htmlFor="auth-password"
                                >
                                    {t('Wachtwoord')}
                                </label>
                                <ModalPasswordInput
                                    id="auth-password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    placeholder={t('Wachtwoord')}
                                />
                                <InputError message={errors.password} />

                                <label className="flex items-center gap-2 text-[13px] text-choc3">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        className="size-4 accent-choc"
                                    />
                                    {t('Onthoud mij')}
                                </label>

                                <MaisonButton
                                    type="submit"
                                    variant="filled"
                                    block
                                    disabled={processing}
                                >
                                    {t('Inloggen')}
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
                            {t('Wachtwoord vergeten?')}
                        </button>
                        <button
                            type="button"
                            className="font-sans tracking-[0.12em] uppercase transition-colors hover:text-choc"
                            onClick={() => switchView('register')}
                        >
                            {t('Nog geen account? Registreren')}
                        </button>
                    </div>
                </>
            )}

            {view === 'register' && (
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
                                    placeholder={t('Uw naam')}
                                    className={modalInputClassName}
                                />
                                <InputError message={errors.name} />

                                <input
                                    type="email"
                                    name="email"
                                    required
                                    autoComplete="email"
                                    placeholder={t('Uw e-mailadres')}
                                    className={modalInputClassName}
                                />
                                <InputError message={errors.email} />

                                <ModalPasswordInput
                                    name="password"
                                    required
                                    autoComplete="new-password"
                                    placeholder={t('Wachtwoord')}
                                />
                                <InputError message={errors.password} />

                                <ModalPasswordInput
                                    name="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                    placeholder={t('Bevestig wachtwoord')}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />

                                <GenderSelect
                                    aria-label={t('Geslacht')}
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
                                    {t('Registreren')}
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
                            {t('Al een account? Inloggen')}
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
                                        placeholder={t('Uw e-mailadres')}
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
                                        {t('Verstuur code')}
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
                                            {t('E-mailadres')}
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
                                            {t('6-cijferige code')}
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
                                            {t('Nieuw wachtwoord instellen')}
                                        </label>
                                        <ModalPasswordInput
                                            id="auth-reset-password"
                                            name="password"
                                            required
                                            autoComplete="new-password"
                                            placeholder={t('Nieuw wachtwoord')}
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="flex flex-col gap-1.5">
                                        <label
                                            htmlFor="auth-reset-password-confirm"
                                            className="font-sans text-[10px] tracking-[0.18em] text-stone uppercase"
                                        >
                                            {t('Bevestig nieuw wachtwoord')}
                                        </label>
                                        <ModalPasswordInput
                                            id="auth-reset-password-confirm"
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                            placeholder={t(
                                                'Bevestig wachtwoord',
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
                                        {t('Wachtwoord opslaan')}
                                    </MaisonButton>

                                    <button
                                        type="button"
                                        className="font-sans text-[11px] tracking-[0.12em] text-stone uppercase transition-colors hover:text-choc"
                                        onClick={() => {
                                            setForgotSent(false);
                                        }}
                                    >
                                        {t('Andere code aanvragen')}
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
                            {t('Terug naar inloggen')}
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
                                            placeholder={t('Herstelcode')}
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
                                            placeholder={t('Authenticatiecode')}
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
                                    {t('Doorgaan')}
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
                                        ? t('Inloggen met authenticatiecode')
                                        : t('Inloggen met herstelcode')}
                                </button>
                            </>
                        )}
                    </Form>
                </>
            )}

            <p className={modalNoteClassName}>
                {t(
                    'Uw gegevens worden veilig behandeld. Alleen wat nodig is voor uw Maison Anversa-account.',
                )}
            </p>
        </MaisonModal>
    );
}
