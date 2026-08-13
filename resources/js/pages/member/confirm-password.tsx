import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import InputError from '@/components/input-error';
import {
    MemberPageHeader,
    MemberPanel,
    MemberSectionTitle,
    memberFieldClassName,
} from '@/components/member/member-ui';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

export default function MemberConfirmPassword() {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    return (
        <>
            <Head title={t('Wachtwoord bevestigen')} />
            <MemberPageHeader
                eyebrow={t('Beveiliging')}
                title={t('Bevestig uw wachtwoord')}
                description={t(
                    'Dit is een beveiligd gedeelte van uw account. Bevestig uw wachtwoord om verder te gaan.',
                )}
            />

            <MemberPanel className="max-w-md">
                <MemberSectionTitle
                    title={t('Wachtwoordcontrole')}
                    description={t(
                        'Vereist voordat u beveiligingsinstellingen bekijkt of wijzigt.',
                    )}
                />
                <Form {...store.form()} resetOnSuccess={['password']}>
                    {({ processing, errors }) => (
                        <div className="space-y-5">
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="password"
                                    className="font-sans text-[10px] tracking-[0.16em] text-gold uppercase"
                                >
                                    {t('Wachtwoord')}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    placeholder={t('Uw huidige wachtwoord')}
                                    autoComplete="current-password"
                                    autoFocus
                                    className={memberFieldClassName}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex flex-wrap items-center gap-3 border-t border-gold/20 pt-5">
                                <Button
                                    className="min-w-40 rounded-none"
                                    disabled={processing}
                                    data-test="confirm-password-button"
                                >
                                    {processing && <Spinner />}
                                    {t('Wachtwoord bevestigen')}
                                </Button>
                                <Link
                                    href={`/${locale}/member`}
                                    className="font-sans text-[11px] tracking-[0.14em] text-sand uppercase no-underline hover:text-gold"
                                >
                                    {t('Annuleren')}
                                </Link>
                            </div>
                        </div>
                    )}
                </Form>
            </MemberPanel>
        </>
    );
}
