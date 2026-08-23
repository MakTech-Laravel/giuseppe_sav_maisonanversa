import { useFlashToast } from '@/hooks/use-flash-toast';
import { useEffect, useState, type CSSProperties } from 'react';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

/**
 * Client-only Sonner host. Rendering the toaster during SSR/hydration mismatches
 * the empty server markup and can break the notification layer.
 */
function Toaster({ ...props }: ToasterProps) {
    const [mounted, setMounted] = useState(false);

    useFlashToast();

    useEffect(() => {
        setMounted(true);
    }, []);

    if (!mounted) {
        return null;
    }

    return (
        <Sonner
            theme="light"
            className="toaster group"
            position="bottom-right"
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                    zIndex: 999999,
                } as CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
