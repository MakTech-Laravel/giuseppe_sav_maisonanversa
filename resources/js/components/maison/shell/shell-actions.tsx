import { createContext, useContext } from 'react';
import type { ReactNode } from 'react';
import type { AuthView } from '@/components/maison/modals/auth-modal';

/**
 * The shell owns the modals, so any page that needs to open one reaches through
 * here rather than inventing its own overlay.
 */
export type ShellActions = {
    openNewsletter: () => void;
    openOrder: () => void;
    openCertificate: () => void;
    openAuth: (view?: AuthView) => void;
};

const ShellActionsContext = createContext<ShellActions | null>(null);

export function ShellActionsProvider({
    value,
    children,
}: {
    value: ShellActions;
    children: ReactNode;
}) {
    return (
        <ShellActionsContext.Provider value={value}>
            {children}
        </ShellActionsContext.Provider>
    );
}

export function useShellActions(): ShellActions {
    const actions = useContext(ShellActionsContext);

    if (!actions) {
        throw new Error('useShellActions must be used inside FrontendLayout.');
    }

    return actions;
}
