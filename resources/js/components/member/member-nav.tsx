import { Link, router, usePage } from '@inertiajs/react';
import { logout } from '@/routes';
import { cn } from '@/lib/utils';

type NavItem = {
    label: string;
    href: string;
    exact?: boolean;
};

const ITEMS: NavItem[] = [
    { label: 'Dashboard', href: '/member', exact: true },
    { label: 'My Heritage', href: '/member/heritage' },
    { label: 'Orders', href: '/member/orders' },
    { label: 'Passport', href: '/member/passport' },
    { label: 'Founding Circle', href: '/member/circle' },
    { label: 'Community', href: 'community' },
    { label: 'Heritage Letter', href: '/member/letter' },
    { label: 'Profile & Account', href: '/member/profile' },
    { label: 'Security', href: '/member/security' },
];

export function MemberNav() {
    const { locale } = usePage().props;
    const { url } = usePage();
    const path = url.split('?')[0].replace(/\/+$/, '') || '/';

    return (
        <nav aria-label="Member" className="w-full shrink-0 md:w-56">
            <ul className="flex gap-1 overflow-x-auto border border-gold/20 bg-cream2 p-2 md:flex-col md:overflow-visible">
                {ITEMS.map((item) => {
                    const href =
                        item.href === 'community'
                            ? `/${locale}/community`
                            : item.href;
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
