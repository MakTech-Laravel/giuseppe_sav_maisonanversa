import { Form, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Monogram } from '@/components/maison/ui/monogram';
import * as requestRoutes from '@/routes/community/sessions/requests';
import type { JoinRequest } from '@/types/session';

type JoinRequestsProps = {
    sessionId: number;
    requests: JoinRequest[];
};

/**
 * The host's waiting list: every pending request holds a seat until the host
 * approves or rejects it.
 */
export function JoinRequests({ sessionId, requests }: JoinRequestsProps) {
    const { t } = useTranslation();
    const { locale } = usePage().props;

    if (requests.length === 0) {
        return null;
    }

    return (
        <div className="border-t border-gold/15 px-7 py-7">
            <h2 className="mb-1 font-sans text-[11px] font-medium tracking-[0.22em] text-choc uppercase">
                {t('Aanvragen')}
            </h2>
            <p className="mb-5 font-sans text-[11px] text-stone">
                {t('Deze spelers wachten op uw goedkeuring.')}
            </p>

            <ul className="flex flex-col gap-3">
                {requests.map((request) => (
                    <li
                        key={request.id}
                        className="flex flex-wrap items-center justify-between gap-3 border border-gold/15 bg-cream px-4 py-3"
                    >
                        <span className="inline-flex min-w-0 items-center gap-3">
                            <Monogram
                                size="md"
                                initials={request.initials}
                                title={request.name}
                            >
                                {request.avatar_url ? (
                                    <img
                                        src={request.avatar_url}
                                        alt={request.name}
                                        className="size-full object-cover"
                                    />
                                ) : undefined}
                            </Monogram>
                            <span className="truncate font-sans text-[11px] tracking-[0.08em] text-choc">
                                {request.name}
                            </span>
                        </span>

                        <span className="flex gap-2">
                            <Form
                                {...requestRoutes.approve.form({
                                    locale,
                                    communitySession: sessionId,
                                    user: request.id,
                                })}
                                options={{ preserveScroll: true }}
                                disableWhileProcessing
                            >
                                {({ processing }) => (
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className={smallSolidButton}
                                    >
                                        {t('Goedkeuren')}
                                    </button>
                                )}
                            </Form>
                            <Form
                                {...requestRoutes.reject.form({
                                    locale,
                                    communitySession: sessionId,
                                    user: request.id,
                                })}
                                options={{ preserveScroll: true }}
                                disableWhileProcessing
                            >
                                {({ processing }) => (
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className={smallOutlineButton}
                                    >
                                        {t('Weigeren')}
                                    </button>
                                )}
                            </Form>
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

const smallSolidButton =
    'cursor-pointer border-none bg-choc px-5 py-2.5 font-sans text-[10px] font-medium tracking-[0.2em] text-cream uppercase transition-colors hover:bg-gold2 disabled:cursor-not-allowed disabled:opacity-50';

const smallOutlineButton =
    'cursor-pointer border border-gold/30 bg-transparent px-5 py-2.5 font-sans text-[10px] font-medium tracking-[0.2em] text-choc uppercase transition-colors hover:border-gold hover:bg-gold/10 disabled:cursor-not-allowed disabled:opacity-50';
