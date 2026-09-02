import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useShellActions } from '@/components/maison/shell/shell-actions';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';

type AuthUser = {
    name: string;
    is_admin: boolean;
    dashboard_url: string;
};

type SharedAuth = {
    user: AuthUser | null;
};

type AuthMenuProps = {
    compact?: boolean;
    className?: string;
};

export function AuthMenu({ compact = false, className }: AuthMenuProps) {
    const { t } = useTranslation();
    const { openAuth } = useShellActions();
    const { auth, locale } = usePage<{ auth?: SharedAuth; locale: string }>()
        .props;
    const user = auth?.user ?? null;
    const [open, setOpen] = useState(false);
    const root = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function onPointerDown(event: MouseEvent): void {
            if (!root.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', onPointerDown);

        return () => document.removeEventListener('mousedown', onPointerDown);
    }, []);

    if (!user) {
        return (
            <button
                type="button"
                onClick={() => openAuth('login')}
                className={cn(
                    'inline-flex min-h-11 items-center font-sans text-[10px] font-light tracking-[0.2em] text-cream uppercase transition-colors hover:text-gold',
                    compact && 'min-h-0 px-2 py-1 text-[9px]',
                    className,
                )}
            >
                {compact ? t('Inloggen') : t('Inloggen')}
            </button>
        );
    }

    const firstName = user.name.split(' ')[0] ?? user.name;
    const profileHref = user.is_admin
        ? `/${locale}/settings/profile`
        : `/${locale}/member/profile`;
    const securityHref = user.is_admin
        ? `/${locale}/settings/security`
        : `/${locale}/member/security`;

    return (
        <div ref={root} className={cn('relative', className)}>
            <button
                type="button"
                aria-expanded={open}
                aria-haspopup="menu"
                onClick={() => setOpen((current) => !current)}
                className={cn(
                    'inline-flex min-h-11 items-center gap-2 font-sans text-[10px] font-light tracking-[0.18em] text-cream uppercase transition-colors hover:text-gold',
                    compact && 'min-h-0 px-2 py-1 text-[9px]',
                )}
            >
                <span
                    aria-hidden="true"
                    className="flex size-8 items-center justify-center rounded-full border border-gold/35 font-serif text-[11px] text-gold"
                >
                    {firstName.charAt(0).toUpperCase()}
                </span>
                {!compact && <span>{firstName}</span>}
            </button>

            {open && (
                <div
                    role="menu"
                    className="absolute top-[calc(100%+0.5rem)] right-0 z-[250] min-w-44 border border-gold/20 bg-choc py-2 shadow-lg"
                >
                    <Link
                        role="menuitem"
                        href={user.dashboard_url}
                        className="block px-4 py-2.5 font-sans text-[10px] tracking-[0.18em] text-cream uppercase transition-colors hover:bg-gold/10 hover:text-gold"
                        onClick={() => setOpen(false)}
                    >
                        {t('Dashboard')}
                    </Link>
                    <Link
                        role="menuitem"
                        href={profileHref}
                        className="block px-4 py-2.5 font-sans text-[10px] tracking-[0.18em] text-cream uppercase transition-colors hover:bg-gold/10 hover:text-gold"
                        onClick={() => setOpen(false)}
                    >
                        {t('Profiel')}
                    </Link>
                    <Link
                        role="menuitem"
                        href={securityHref}
                        className="block px-4 py-2.5 font-sans text-[10px] tracking-[0.18em] text-cream uppercase transition-colors hover:bg-gold/10 hover:text-gold"
                        onClick={() => setOpen(false)}
                    >
                        {t('Beveiliging')}
                    </Link>
                    <button
                        type="button"
                        role="menuitem"
                        className="block w-full px-4 py-2.5 text-left font-sans text-[10px] tracking-[0.18em] text-gold2 uppercase transition-colors hover:bg-gold/10 hover:text-gold"
                        onClick={() => {
                            setOpen(false);
                            router.flushAll();
                            router.post(logout.url());
                        }}
                    >
                        {t('Uitloggen')}
                    </button>
                </div>
            )}
        </div>
    );
}
