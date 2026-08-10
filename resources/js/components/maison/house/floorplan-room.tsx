import type { MouseEvent, ReactNode } from 'react';
import { usePageTransition } from '@/components/maison/cinematic/page-transition';
import { useLocale } from '@/hooks/use-locale';
import { maisonUrl } from '@/lib/maison-navigation';
import type { MaisonPage } from '@/lib/maison-navigation';

type FloorplanRoomProps = {
    to: MaisonPage;
    children: ReactNode;
};

/**
 * An SVG `<a>` that navigates through the cinematic page transition.
 *
 * Deliberately an SVG anchor rather than an HTML one: wrapping a room group in
 * a foreignObject would break the hover fill, and a `div` with an onclick (as
 * the prototype used) is neither crawlable nor keyboard-reachable.
 */
export function FloorplanRoom({ to, children }: FloorplanRoomProps) {
    const { locale } = useLocale();
    const navigate = usePageTransition();
    const href = maisonUrl(to, locale);

    function onClick(event: MouseEvent<HTMLAnchorElement>) {
        const opensElsewhere =
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey ||
            event.button !== 0;

        if (event.defaultPrevented || opensElsewhere) {
            return;
        }

        event.preventDefault();
        navigate(href);
    }

    return (
        <a href={href} className="room" onClick={onClick}>
            {children}
        </a>
    );
}
