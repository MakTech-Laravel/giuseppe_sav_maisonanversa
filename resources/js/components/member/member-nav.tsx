import { Link, router, usePage } from '@inertiajs/react';
import { logout } from '@/routes';
import { cn } from '@/lib/utils';

type NavItem = {
    label: string;
    href: string;
    exact?: boolean;
};

export function MemberNav() {
    const { locale } = usePage().props;
    const { url } = usePage();
    const path = url.split('?')[0].replace(/\/+$/, '') || '/';

    const items: NavItem[] = [
        { label: 'Dashboard', href: `/${locale}/member`, exact: true },
        { label: 'Orders', href: `/${locale}/member/orders` },
        { label: 'Founding Circle', href: `/${locale}/member/circle` },
        { label: 'Community', href: `/${locale}/community` },
        { label: 'Heritage Letter', href: `/${locale}/member/letter` },
        { label: 'Profile & Account', href: `/${locale}/member/profile` },
        { label: 'Security', href: `/${locale}/member/security` },
    ];

    return (
        <nav
            aria-label="Member"
            className="w-full shrink-0 md:sticky md:top-20 md:w-56 md:self-start"
        >
            <ul className="flex gap-1 overflow-x-auto border border-gold/25 bg-choc3 p-2 md:max-h-[calc(100vh-6rem)] md:flex-col md:overflow-y-auto">
                {items.map((item) => {
                    const href = item.href;
                    const active = item.exact
                        ? path === href
                        : path === href || path.startsWith(`${href}/`);

                    return (
                        <li key={item.label} className="shrink-0">
                            <Link
                                href={href}
                                className={cn(
                                    'block min-h-11 px-3 py-2.5 font-sans text-[10px] tracking-[0.18em] uppercase transition-colors md:min-h-0',
                                    active
                                        ? 'bg-gold text-choc'
                                        : 'text-sand hover:bg-choc2 hover:text-cream',
                                )}
                            >
                                {item.label}
                            </Link>
                        </li>
                    );
                })}
                <li className="shrink-0 md:mt-2 md:border-t md:border-gold/20 md:pt-2">
                    <button
                        type="button"
                        onClick={() => {
                            router.flushAll();
                            router.post(logout.url());
                        }}
                        className="block min-h-11 w-full px-3 py-2.5 text-left font-sans text-[10px] tracking-[0.18em] text-gold uppercase transition-colors hover:bg-choc2 hover:text-cream md:min-h-0"
                    >
                        Logout
                    </button>
                </li>
            </ul>
        </nav>
    );
}
