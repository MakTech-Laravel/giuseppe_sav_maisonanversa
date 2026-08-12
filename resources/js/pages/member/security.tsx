import { Form, Head } from '@inertiajs/react';
import { useRef } from 'react';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

export default function MemberSecurity({
    canManageTwoFactor = false,
    twoFactorEnabled = false,
}: {
    canManageTwoFactor?: boolean;
    twoFactorEnabled?: boolean;
}) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <>
            <Head title="Security" />
            <MemberPageHeader
                eyebrow="Account"
                title="Security"
                description="Update your password. Two-factor settings remain available in the full security screen when enabled."
            />

            <MemberPanel className="max-w-xl space-y-6">
                <Form
                    {...SecurityController.update.form()}
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
                    className="space-y-5"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="current_password">
                                    Current password
                                </Label>
                                <PasswordInput
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    name="current_password"
                                    autoComplete="current-password"
                                />
                                <InputError message={errors.current_password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">New password</Label>
                                <PasswordInput
                                    id="password"
                                    ref={passwordInput}
                                    name="password"
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button disabled={processing}>
                                Update password
                            </Button>
                        </>
                    )}
                </Form>

                {canManageTwoFactor && (
                    <p className="font-sans text-[11px] tracking-[0.12em] text-stone uppercase">
                        Two-factor authentication ·{' '}
                        {twoFactorEnabled ? 'Enabled' : 'Available in settings'}
                    </p>
                )}
            </MemberPanel>
        </>
    );
}
