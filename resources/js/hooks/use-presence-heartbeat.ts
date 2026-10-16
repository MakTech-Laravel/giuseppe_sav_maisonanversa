import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { getXsrfToken } from '@/echo';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';

const INTERVAL_MS = 60_000;

/**
 * Keeps users.last_seen_at fresh while the authenticated tab is visible so
 * chat digests can treat the member as "active".
 */
export function usePresenceHeartbeat(): void {
    const { auth } = usePage().props;
    const userId = auth?.user?.id;

    useEffect(() => {
        if (!userId) {
            return;
        }

        const locale = wayfinderLocale();
        const url = `/${locale}/member/presence`;
        let timer: ReturnType<typeof setInterval> | null = null;

        const ping = () => {
            if (document.visibilityState !== 'visible') {
                return;
            }

            void fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                credentials: 'same-origin',
            }).catch(() => {
                // Presence is best-effort; digests still run on unread.
            });
        };

        ping();
        timer = setInterval(ping, INTERVAL_MS);

        const onVisibility = () => {
            if (document.visibilityState === 'visible') {
                ping();
            }
        };

        document.addEventListener('visibilitychange', onVisibility);

        return () => {
            if (timer) {
                clearInterval(timer);
            }

            document.removeEventListener('visibilitychange', onVisibility);
        };
    }, [userId]);
}
