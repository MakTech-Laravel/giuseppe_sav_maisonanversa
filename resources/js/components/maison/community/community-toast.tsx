import { useCallback, useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Fixed bottom toast matching the prototype's `.comm-notification`.
 * Used for login, compose, session join and event RSVP feedback.
 */
export function useCommunityToast() {
    const [message, setMessage] = useState('');
    const [visible, setVisible] = useState(false);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | undefined>(
        undefined,
    );

    const show = useCallback((text: string) => {
        setMessage(text);
        setVisible(true);

        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        timeoutRef.current = setTimeout(() => {
            setVisible(false);
        }, 3200);
    }, []);

    useEffect(() => {
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, []);

    return { message, visible, show };
}

type CommunityToastProps = {
    message: string;
    visible: boolean;
};

export function CommunityToast({ message, visible }: CommunityToastProps) {
    return (
        <div
            role="status"
            aria-live="polite"
            className={cn(
                'pointer-events-none fixed bottom-10 left-1/2 z-300 -translate-x-1/2 border border-gold bg-choc px-7 py-3.5 font-sans text-[11px] tracking-[0.15em] text-gold uppercase transition-opacity duration-300',
                visible ? 'opacity-100' : 'opacity-0',
            )}
        >
            {message}
        </div>
    );
}
