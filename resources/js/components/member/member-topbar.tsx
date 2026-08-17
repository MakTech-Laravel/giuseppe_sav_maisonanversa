import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MemberNotificationBell } from '@/components/member/member-notification-bell';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { LanguageSwitcher } from '@/components/maison/shell/language-switcher';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

export function MemberTopbar({
    name,
    avatarUrl,
}: {
    name: string;
    avatarUrl?: string | null;
}) {
    const { t } = useTranslation();
    const { locale } = usePage().props;
    const getInitials = useInitials();

    return (
        <header className="sticky top-0 z-40 border-b border-gold/20 bg-choc2 text-cream">
            <div className="mx-auto flex h-16 w-full max-w-320 items-center justify-between gap-3 px-4 md:px-10">
                <Link
                    href={`/${locale}`}
                    className="flex min-w-0 items-center gap-3 no-underline"
                >
                    <PlaceholderImage
                        asset="logo-icon"
                        ratio="1 / 1"
                        alt=""
                        captioned={false}
                        className="size-8 shrink-0"
                    />
                    <div className="min-w-0">
                        <p className="truncate font-serif text-[13px] tracking-[0.18em] text-cream uppercase sm:text-[15px]">
                            Maison Anversa
                        </p>
                        <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                            {t('Lid')}
                        </p>
                    </div>
                </Link>
                <div className="flex items-center gap-3 md:gap-4">
                    <MemberNotificationBell />
                    <LanguageSwitcher className="gap-1 [&_button]:min-h-8 [&_button]:min-w-8 [&_button]:px-1.5 [&_button]:py-1 [&_button]:text-[9px]" />
                    <span
                        aria-hidden="true"
                        className="hidden h-5 w-px bg-gold/25 sm:block"
                    />
                    <div className="flex items-center gap-3">
                        <Avatar className="size-8 overflow-hidden rounded-full border border-gold/30">
                            {avatarUrl ? (
                                <AvatarImage src={avatarUrl} alt={name} />
                            ) : null}
                            <AvatarFallback className="rounded-full bg-choc3 font-sans text-[11px] text-cream">
                                {getInitials(name)}
                            </AvatarFallback>
                        </Avatar>
                        <p className="hidden font-sans text-[11px] tracking-[0.12em] text-sand uppercase md:block">
                            {name}
                        </p>
                    </div>
                </div>
            </div>
        </header>
    );
}
