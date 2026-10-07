import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { takePendingSessionJoin } from '@/lib/pending-session-join';
import * as sessionRoutes from '@/routes/community/sessions';

type JoinableSession = {
    id: string;
    can_join: boolean;
};

/**
 * After a guest clicks Join, stores a session id, and logs in, finish the
 * join once against a still-open card on the current page.
 */
export function useResumePendingSessionJoin(
    sessions: readonly JoinableSession[],
): void {
    const { auth, locale } = usePage().props;
    const resumed = useRef(false);
    const sessionKey = sessions.map((session) => session.id).join(',');

    useEffect(() => {
        if (resumed.current || !auth?.user) {
            return;
        }

        const pendingId = takePendingSessionJoin();

        if (pendingId === null) {
            return;
        }

        const match = sessions.find(
            (session) => session.id === pendingId && session.can_join,
        );

        if (!match) {
            return;
        }

        resumed.current = true;

        router.post(
            sessionRoutes.join({
                locale,
                communitySession: Number(pendingId),
            }).url,
            {},
            { preserveScroll: true },
        );
        // sessionKey tracks id changes without depending on the array identity.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [auth?.user, locale, sessionKey]);
}
