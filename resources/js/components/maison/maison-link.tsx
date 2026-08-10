import type { AnchorHTMLAttributes, MouseEvent } from 'react';
import { usePageTransition } from '@/components/maison/cinematic/page-transition';
import { useLocale } from '@/hooks/use-locale';
import { maisonUrl } from '@/lib/maison-navigation';
import type { MaisonPage } from '@/lib/maison-navigation';

type MaisonLinkProps = Omit<AnchorHTMLAttributes<HTMLAnchorElement>, 'href'> & {
    to: MaisonPage;
    /** Fragment to append, for deep links into a section of the target page. */
    hash?: string;
};

/**
 * An internal link that navigates through the cinematic page transition.
 *
 * Deliberately a real `<a href>` rather than Inertia's `<Link>`: the href has to
 * be present and correct for the crawler, for a middle-click and for the status
 * bar, and only a left-click without a modifier is intercepted. The prototype's
 * links were all `href="#"` with the destination hidden in an onclick handler,
 * so none of that worked.
 */
export function MaisonLink({ to, hash, onClick, ...props }: MaisonLinkProps) {
    const { locale } = useLocale();
    const navigate = usePageTransition();
    const href = maisonUrl(to, locale) + (hash ? `#${hash}` : '');

    function handleClick(event: MouseEvent<HTMLAnchorElement>) {
        onClick?.(event);

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

    return <a href={href} onClick={handleClick} {...props} />;
}
