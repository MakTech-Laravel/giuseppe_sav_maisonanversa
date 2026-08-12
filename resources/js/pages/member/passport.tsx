import { Head } from '@inertiajs/react';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';

type PassportPage = { title: string; body: string };

export default function MemberPassport({
    passport,
}: {
    passport: { editionNumber: string; pages: PassportPage[] };
}) {
    return (
        <>
            <Head title="Digital Heritage Passport" />
            <MemberPageHeader
                eyebrow={`No.${passport.editionNumber}`}
                title="Digital Heritage Passport"
                description="Four pages of the physical passport — a reading copy until the edition ships."
            />

            <div className="grid gap-4 md:grid-cols-2">
                {passport.pages.map((page, index) => (
                    <MemberPanel key={page.title} className="min-h-48 bg-choc">
                        <p className="font-sans text-[9px] tracking-[0.25em] text-gold uppercase">
                            Page {String(index + 1).padStart(2, '0')}
                        </p>
                        <h2 className="mt-3 font-serif text-[26px] text-cream">
                            {page.title}
                        </h2>
                        <p className="mt-4 text-[15px] leading-[1.8] text-sand">
                            {page.body}
                        </p>
                    </MemberPanel>
                ))}
            </div>
        </>
    );
}
