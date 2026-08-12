import { Link } from '@inertiajs/react';
import { PlaceholderImage } from '@/components/maison/placeholder-image';

export function MemberTopbar({ name }: { name: string }) {
    return (
        <header className="border-b border-gold/20 bg-choc2 text-cream">
            <div className="mx-auto flex h-16 w-full max-w-320 items-center justify-between px-6 md:px-10">
                <Link
                    href="/member"
                    className="flex items-center gap-3 no-underline"
                >
                    <PlaceholderImage
                        asset="logo-icon"
                        ratio="1 / 1"
                        alt=""
                        captioned={false}
                        className="size-8"
                    />
                    <div>
                        <p className="font-serif text-[15px] tracking-[0.18em] text-cream uppercase">
                            Maison Anversa
                        </p>
                        <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                            Member
                        </p>
                    </div>
                </Link>
                <p className="hidden font-sans text-[11px] tracking-[0.12em] text-sand uppercase md:block">
                    {name}
                </p>
            </div>
        </header>
    );
}
