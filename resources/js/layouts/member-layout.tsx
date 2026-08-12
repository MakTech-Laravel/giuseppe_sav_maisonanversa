import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { MemberNav } from '@/components/member/member-nav';
import { MemberTopbar } from '@/components/member/member-topbar';

/**
 * Maison-styled shell for the member area: cream ground, chocolate chrome,
 * gold accents — same vocabulary as the public site without the cinematic
 * public header.
 */
export default function MemberLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-screen bg-cream font-serif text-choc">
            <MemberTopbar name={auth.user?.name ?? ''} />
            <div className="mx-auto flex w-full max-w-320 flex-col gap-8 px-6 py-8 md:flex-row md:px-10">
                <MemberNav />
                <main className="min-w-0 flex-1 pb-16">{children}</main>
            </div>
        </div>
    );
}
