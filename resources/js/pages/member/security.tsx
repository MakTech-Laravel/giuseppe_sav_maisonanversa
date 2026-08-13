import { Form, Head } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/input-error';
import {
    MemberPageHeader,
    MemberPanel,
    MemberSectionTitle,
    MemberStatusPill,
    memberFieldClassName,
} from '@/components/member/member-ui';
import PasswordInput from '@/components/password-input';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { disable, enable } from '@/routes/two-factor';

export default function MemberSecurity({
    canManageTwoFactor = false,
    requiresConfirmation = false,
    twoFactorEnabled = false,
    passwordRules = '',
}: {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    passwordRules?: string;
}) {
    const { t } = useTranslation();
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        clearTwoFactorAuthData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors: twoFactorErrors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState(false);
    const prevTwoFactorEnabled = useRef(twoFactorEnabled);

    useEffect(() => {
        if (prevTwoFactorEnabled.current && !twoFactorEnabled) {
            clearTwoFactorAuthData();
        }

        prevTwoFactorEnabled.current = twoFactorEnabled;
    }, [twoFactorEnabled, clearTwoFactorAuthData]);

    return (
        <>
            <Head title={t('Beveiliging')} />
            <MemberPageHeader
                eyebrow={t('Account')}
                title={t('Beveiliging')}
                description={t(
                    'Bescherm uw Maison Anversa-account met een sterk wachtwoord en optionele tweestapsverificatie.',
                )}
            />

            <div className="space-y-6">
                <MemberPanel>
                    <MemberSectionTitle
                        title={t('Wachtwoord')}
                        description={t(
                            'Gebruik een lang, uniek wachtwoord. U hebt uw huidige wachtwoord nodig om het te wijzigen.',
                        )}
                    />
                    <Form
                        {...SecurityController.update.form(wayfinderLocale())}
                        options={{ preserveScroll: true }}
                        resetOnError={[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]}
                        resetOnSuccess
                        onError={(errors) => {
                            if (errors.password) {
                                passwordInput.current?.focus();
                            }

                            if (errors.current_password) {
                                currentPasswordInput.current?.focus();
                            }
                        }}
                        className="max-w-xl space-y-5"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="current_password"
                                        className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                    >
                                        {t('Huidig wachtwoord')}
                                    </Label>
                                    <PasswordInput
                                        id="current_password"
                                        ref={currentPasswordInput}
                                        name="current_password"
                                        autoComplete="current-password"
                                        className={memberFieldClassName}
                                    />
                                    <InputError
                                        message={errors.current_password}
                                    />
                                </div>

                                <div className="grid gap-5 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password"
                                            className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                        >
                                            {t('Nieuw wachtwoord')}
                                        </Label>
                                        <PasswordInput
                                            id="password"
                                            ref={passwordInput}
                                            name="password"
                                            autoComplete="new-password"
                                            passwordrules={passwordRules}
                                            className={memberFieldClassName}
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password_confirmation"
                                            className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                        >
                                            {t('Bevestig wachtwoord')}
                                        </Label>
                                        <PasswordInput
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                            passwordrules={passwordRules}
                                            className={memberFieldClassName}
                                        />
                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="flex justify-end border-t border-gold/20 pt-5">
                                    <Button
                                        disabled={processing}
                                        className="min-w-40 rounded-none"
                                    >
                                        {t('Wachtwoord bijwerken')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </MemberPanel>

                {canManageTwoFactor && (
                    <MemberPanel>
                        <div className="mb-6 flex flex-wrap items-start justify-between gap-3 border-b border-gold/20 pb-4">
                            <div>
                                <h2 className="font-serif text-[22px] text-cream">
                                    {t('Tweestapsverificatie')}
                                </h2>
                                <p className="mt-1.5 max-w-xl text-[13px] leading-relaxed text-sand">
                                    {t(
                                        'Voeg een extra stap toe bij het inloggen met een code uit een authenticator-app op uw telefoon.',
                                    )}
                                </p>
                            </div>
                            <MemberStatusPill
                                tone={
                                    twoFactorEnabled ? 'success' : 'warn'
                                }
                            >
                                {twoFactorEnabled
                                    ? t('Ingeschakeld')
                                    : t('Niet ingeschakeld')}
                            </MemberStatusPill>
                        </div>

                        {twoFactorEnabled ? (
                            <div className="max-w-xl space-y-5">
                                <p className="text-[14px] leading-[1.7] text-sand">
                                    {t(
                                        'U wordt bij het inloggen gevraagd om een beveiligingscode, die u uit een TOTP-ondersteunde app op uw telefoon haalt.',
                                    )}
                                </p>

                                <Form {...disable.form()}>
                                    {({ processing }) => (
                                        <Button
                                            variant="destructive"
                                            type="submit"
                                            className="rounded-none"
                                            disabled={processing}
                                        >
                                            {t('2FA uitschakelen')}
                                        </Button>
                                    )}
                                </Form>

                                <TwoFactorRecoveryCodes
                                    recoveryCodesList={recoveryCodesList}
                                    fetchRecoveryCodes={fetchRecoveryCodes}
                                    errors={twoFactorErrors}
                                />
                            </div>
                        ) : (
                            <div className="max-w-xl space-y-5">
                                <p className="text-[14px] leading-[1.7] text-sand">
                                    {t(
                                        'Wanneer ingeschakeld, wordt u bij het inloggen gevraagd om een beveiligingscode. Deze code komt uit een TOTP-ondersteunde app op uw telefoon.',
                                    )}
                                </p>

                                {hasSetupData ? (
                                    <Button
                                        type="button"
                                        className="rounded-none"
                                        onClick={() => setShowSetupModal(true)}
                                    >
                                        <ShieldCheck />
                                        {t('Setup voortzetten')}
                                    </Button>
                                ) : (
                                    <Form
                                        {...enable.form()}
                                        onSuccess={() =>
                                            setShowSetupModal(true)
                                        }
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                className="rounded-none"
                                                disabled={processing}
                                            >
                                                {t('2FA inschakelen')}
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        )}

                        <TwoFactorSetupModal
                            isOpen={showSetupModal}
                            onClose={() => setShowSetupModal(false)}
                            requiresConfirmation={requiresConfirmation}
                            twoFactorEnabled={twoFactorEnabled}
                            qrCodeSvg={qrCodeSvg}
                            manualSetupKey={manualSetupKey}
                            clearSetupData={clearSetupData}
                            fetchSetupData={fetchSetupData}
                            errors={twoFactorErrors}
                        />
                    </MemberPanel>
                )}
            </div>
        </>
    );
}
