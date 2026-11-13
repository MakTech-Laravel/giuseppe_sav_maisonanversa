import { Form, Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import TextLink from '@/components/text-link';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Verify email')} />

            {status === 'verification-link-sent' && (
                <div className="mb-6 border border-gold/30 bg-cream2 px-4 py-3 text-center font-sans text-[13px] leading-[1.6] text-choc3">
                    {t(
                        'A new verification link has been sent to your email address.',
                    )}
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <MaisonButton
                            type="submit"
                            variant="filled"
                            block
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            {processing
                                ? t('Working...')
                                : t('Resend verification email')}
                        </MaisonButton>

                        <TextLink
                            href={logout()}
                            className="mx-auto block font-sans text-[10px] tracking-[0.2em] text-stone uppercase no-underline hover:text-gold"
                        >
                            {t('Log out')}
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}

VerifyEmail.layout = {
    title: 'Verify email',
    description:
        'Confirm your email address using the link we just sent you.',
};
