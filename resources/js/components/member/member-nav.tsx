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
        { label: 'My Heritage', href: `/${locale}/member/heritage` },
        { label: 'Orders', href: `/${locale}/member/orders` },
        { label: 'Passport', href: `/${locale}/member/passport` },
        { label: 'Founding Circle', href: `/${locale}/member/circle` },
        { label: 'Community', href: `/${locale}/community` },
        { label: 'Heritage Letter', href: `/${locale}/member/letter` },
        { label: 'Profile & Account', href: `/${locale}/member/profile` },
        { label: 'Security', href: `/${locale}/member/security` },
    ];

    return (
        <nav aria-label="Member" className="w-full shrink-0 md:w-56">
            <ul className="flex gap-1 overflow-x-auto border border-gold/20 bg-cream2 p-2 md:flex-col md:overflow-visible">
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
                                        ? 'bg-choc text-cream'
                                        : 'text-choc3 hover:bg-gold/10 hover:text-choc',
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
                        className="block min-h-11 w-full px-3 py-2.5 text-left font-sans text-[10px] tracking-[0.18em] text-gold2 uppercase transition-colors hover:bg-gold/10 hover:text-choc md:min-h-0"
                    >
                        Logout
                    </button>
                </li>
            </ul>
        </nav>
    );
}
