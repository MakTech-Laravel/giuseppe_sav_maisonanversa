import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

function sidebarNavItems(): NavItem[] {
    const locale = wayfinderLocale();

    return [
        {
            title: 'Profile',
            href: edit(locale),
            icon: null,
        },
        {
            title: 'Security',
            href: editSecurity(locale),
            icon: null,
        },
    ];
}

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const navItems = sidebarNavItems();

    return (
        <div className="px-4 py-8 sm:px-6 lg:px-8">
            <header className="mb-8 space-y-2 border-b border-border pb-6">
                <p className="text-[11px] font-medium tracking-[0.18em] text-muted-foreground uppercase">
                    Account
                </p>
                <h1 className="font-serif text-3xl tracking-tight text-foreground sm:text-4xl">
                    Settings
                </h1>
                <p className="max-w-2xl text-sm text-muted-foreground">
                    Manage your profile photo, account details, and security.
                </p>
            </header>

            <div className="flex flex-col gap-8 lg:flex-row lg:gap-10">
                <aside className="w-full shrink-0 lg:w-52">
                    <nav
                        className="flex gap-1 overflow-x-auto lg:flex-col lg:space-y-1 lg:overflow-visible"
                        aria-label="Settings"
                    >
                        {navItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn(
                                    'justify-start rounded-md px-3',
                                    isCurrentOrParentUrl(item.href)
                                        ? 'bg-primary/15 text-primary hover:bg-primary/20 hover:text-primary'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                )}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="lg:hidden" />

                <div className="min-w-0 flex-1">
                    <section className="mx-auto w-full max-w-3xl space-y-8">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
