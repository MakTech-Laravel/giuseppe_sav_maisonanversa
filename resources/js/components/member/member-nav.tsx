import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { logout } from '@/routes';

type NavItem = {
    label: string;
    href: string;
    exact?: boolean;
};

type MemberNavListProps = {
    items: NavItem[];
    path: string;
    onNavigate?: () => void;
};

function MemberNavList({ items, path, onNavigate }: MemberNavListProps) {
    const { t } = useTranslation();

    return (
        <ul className="flex flex-col gap-1 p-2">
            {items.map((item) => {
                const href = item.href;
                const active = item.exact
                    ? path === href
                    : path === href || path.startsWith(`${href}/`);

                return (
                    <li key={item.href}>
                        <Link
                            href={href}
                            onClick={onNavigate}
                            className={cn(
                                'block px-3 py-2.5 font-sans text-[10px] tracking-[0.18em] uppercase transition-colors',
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
            <li className="mt-2 border-t border-gold/20 pt-2">
                <button
                    type="button"
                    onClick={() => {
                        onNavigate?.();
                        router.flushAll();
                        router.post(logout.url());
                    }}
                    className="block w-full px-3 py-2.5 text-left font-sans text-[10px] tracking-[0.18em] text-gold uppercase transition-colors hover:bg-choc2 hover:text-cream"
                >
                    {t('Uitloggen')}
                </button>
            </li>
        </ul>
    );
}

export function MemberNav() {
    const { t } = useTranslation();
    const { locale, auth } = usePage().props;
    const { url } = usePage();
    const path = url.split('?')[0].replace(/\/+$/, '') || '/';
    const [open, setOpen] = useState(false);
    const isFoundingCircle = Boolean(auth?.user?.is_founding_circle);

    const items: NavItem[] = [
        { label: t('Dashboard'), href: `/${locale}/member`, exact: true },
        { label: t('Bestellingen'), href: `/${locale}/member/orders` },
        {
            label: t('Founding Circle'),
            href: `/${locale}/member/circle`,
        },
        {
            label: t('Lidpaspoort'),
            href: `/${locale}/member/lidpaspoort`,
        },
        ...(isFoundingCircle
            ? [
                  {
                      label: t('Mijn Heritage'),
                      href: `/${locale}/member/heritage`,
                  },
                  {
                      label: t('Digitaal Heritage Passport'),
                      href: `/${locale}/member/passport`,
                  },
              ]
            : []),
        { label: t('Gemeenschap'), href: `/${locale}/community`, exact: true },
        { label: t('Sessies'), href: `/${locale}/community/sessions` },
        { label: t('Events'), href: `/${locale}/community/events` },
        {
            label: t('Heritage Letter'),
            href: `/${locale}/member/letter`,
        },
        {
            label: t('E-mailvoorkeuren'),
            href: `/${locale}/member/email-preferences`,
        },
        {
            label: t('Profiel & account'),
            href: `/${locale}/member/profile`,
        },
        { label: t('Beveiliging'), href: `/${locale}/member/security` },
    ];

    return (
        <>
            <div className="md:hidden">
                <Sheet open={open} onOpenChange={setOpen}>
                    <SheetTrigger asChild>
                        <button
                            type="button"
                            className="border border-gold/25 bg-choc3 px-4 py-2.5 font-sans text-[10px] tracking-[0.18em] text-cream uppercase transition-colors hover:border-gold/40 hover:bg-choc2"
                        >
                            {t('Menu')}
                        </button>
                    </SheetTrigger>
                    <SheetContent
                        side="left"
                        className="gap-0 border-gold/25 bg-choc3 p-0 text-cream [&>button]:text-cream [&>button]:hover:bg-choc2 [&>button]:hover:text-gold [&>button]:focus:ring-gold"
                    >
                        <SheetHeader className="border-b border-gold/20 px-4 py-4">
                            <SheetTitle className="font-sans text-[10px] font-medium tracking-[0.18em] text-cream uppercase">
                                {t('Menu')}
                            </SheetTitle>
                        </SheetHeader>
                        <nav aria-label={t('Lid')} className="overflow-y-auto">
                            <MemberNavList
                                items={items}
                                path={path}
                                onNavigate={() => setOpen(false)}
                            />
                        </nav>
                    </SheetContent>
                </Sheet>
            </div>

            <nav
                aria-label={t('Lid')}
                className="hidden w-full shrink-0 md:sticky md:top-20 md:block md:w-56 md:self-start"
            >
                <div className="max-h-[calc(100vh-6rem)] overflow-y-auto border border-gold/25 bg-choc3">
                    <MemberNavList items={items} path={path} />
                </div>
            </nav>
        </>
    );
}
