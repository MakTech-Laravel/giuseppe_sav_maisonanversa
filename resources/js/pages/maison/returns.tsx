import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';
import { Wrap } from '@/components/maison/ui/section';

export default function MaisonReturns({
    returnAddress,
    loginRedirect,
}: {
    returnAddress: string;
    loginRedirect: string;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const guestForm = useForm({
        reference: '',
        email: '',
    });

    /**
     * Always hit the protected member orders URL. Guests get url.intended set
     * by auth middleware, then the home login modal; after login,
     * PostLoginRedirectService honors that intended path.
     */
    function startReturn() {
        window.location.assign(loginRedirect);
    }

    function requestGuestLink(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        guestForm.post(`/${locale}/returns/guest-link`, {
            preserveScroll: true,
            onSuccess: () => guestForm.reset(),
        });
    }

    return (
        <>
            <MaisonSeoHead />
            <Head title={t('Returns')} />

            <PageHero
                eyebrow={t('Shipping & Returns')}
                title={t('Return within 14 days')}
                subtitle={t(
                    'You can start a return via My account → Orders. The legal cooling-off period starts the day after delivery.',
                )}
            >
                <div className="mx-auto flex max-w-3xl flex-col items-center gap-6 sm:gap-8">
                    <div className="flex flex-wrap items-center justify-center gap-3">
                        <button
                            type="button"
                            onClick={startReturn}
                            className="inline-flex min-h-11 cursor-pointer items-center justify-center border border-cream bg-cream px-6 font-sans text-[10px] tracking-[0.2em] text-choc uppercase"
                        >
                            {t('Start a return')}
                        </button>
                        <Link
                            href={`/${locale}/shipping`}
                            className="inline-flex min-h-11 items-center justify-center border border-cream/40 px-6 font-sans text-[10px] tracking-[0.2em] text-cream uppercase no-underline"
                        >
                            {t('Shipping & returns policy')}
                        </Link>
                    </div>
                    <aside className="w-full max-w-sm border border-gold/25 bg-choc/40 px-5 py-4 text-center backdrop-blur-sm">
                        <p className="font-sans text-[10px] tracking-[0.2em] text-sand uppercase">
                            {t('Return address')}
                        </p>
                        <p className="mt-2 font-sans text-sm leading-relaxed whitespace-pre-line text-cream">
                            {returnAddress}
                        </p>
                    </aside>
                </div>
            </PageHero>

            <div className="bg-cream py-14">
                <Wrap className="max-w-xl px-6 md:px-10">
                    <h2 className="font-serif text-2xl text-choc">
                        {t('Guest order?')}
                    </h2>
                    <p className="mt-3 font-serif text-base leading-relaxed text-stone">
                        {t(
                            'Ordered without an account? Enter your order number and email. We will send you a one-time link to start a return.',
                        )}
                    </p>

                    <form
                        onSubmit={requestGuestLink}
                        className="mt-8 flex flex-col gap-4"
                    >
                        <label className="flex flex-col gap-1.5">
                            <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                                {t('Order number')}
                            </span>
                            <input
                                type="text"
                                value={guestForm.data.reference}
                                onChange={(event) =>
                                    guestForm.setData(
                                        'reference',
                                        event.target.value,
                                    )
                                }
                                placeholder="MA-2026-0001"
                                autoComplete="off"
                                className="border border-gold/20 bg-cream2 px-4 py-3 font-serif text-base text-choc outline-none focus:border-gold2"
                                required
                            />
                            {guestForm.errors.reference && (
                                <span className="font-sans text-xs text-red-700">
                                    {guestForm.errors.reference}
                                </span>
                            )}
                        </label>

                        <label className="flex flex-col gap-1.5">
                            <span className="font-sans text-[9px] tracking-[0.22em] text-stone uppercase">
                                {t('Email used for the order')}
                            </span>
                            <input
                                type="email"
                                value={guestForm.data.email}
                                onChange={(event) =>
                                    guestForm.setData(
                                        'email',
                                        event.target.value,
                                    )
                                }
                                autoComplete="email"
                                className="border border-gold/20 bg-cream2 px-4 py-3 font-serif text-base text-choc outline-none focus:border-gold2"
                                required
                            />
                            {guestForm.errors.email && (
                                <span className="font-sans text-xs text-red-700">
                                    {guestForm.errors.email}
                                </span>
                            )}
                        </label>

                        <button
                            type="submit"
                            disabled={guestForm.processing}
                            className="mt-2 inline-flex min-h-11 cursor-pointer items-center justify-center bg-choc px-6 font-sans text-[10px] tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 disabled:opacity-50"
                        >
                            {t('Send return link')}
                        </button>
                    </form>
                </Wrap>
            </div>
        </>
    );
}
