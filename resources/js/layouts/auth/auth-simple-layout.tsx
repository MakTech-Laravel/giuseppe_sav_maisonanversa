import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { useLocale } from '@/hooks/use-locale';
import { maisonUrl } from '@/lib/maison-navigation';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { locale } = useLocale();
    const { t } = useTranslation();

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center bg-cream px-6 py-10 text-choc">
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,_rgba(141,112,90,0.1),_transparent_55%)]"
            />

            <div className="relative w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-5">
                        <Link
                            href={maisonUrl('home', locale)}
                            className="flex flex-col items-center gap-3"
                        >
                            <div className="flex size-16 items-center justify-center overflow-hidden border border-gold">
                                <PlaceholderImage
                                    asset="logo-icon"
                                    ratio="1 / 1"
                                    alt="Maison Anversa"
                                    captioned={false}
                                    className="size-full"
                                />
                            </div>
                            <span className="font-serif text-[13px] tracking-[0.28em] text-choc uppercase">
                                Maison Anversa
                            </span>
                            <span className="sr-only">{title ? t(title) : null}</span>
                        </Link>

                        <div className="space-y-2.5 text-center">
                            {title ? (
                                <h1 className="font-serif text-[28px] font-medium text-choc">
                                    {t(title)}
                                </h1>
                            ) : null}
                            {description ? (
                                <p className="text-center text-[14px] leading-[1.7] text-choc3">
                                    {t(description)}
                                </p>
                            ) : null}
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
