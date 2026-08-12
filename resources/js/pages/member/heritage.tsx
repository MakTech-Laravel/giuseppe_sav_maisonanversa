import { Head } from '@inertiajs/react';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';

type Heritage = {
    editionNumber: string;
    status: string;
    deliveryWindow: string;
    certificate: string;
    passport: string;
};

export default function MemberHeritage({ heritage }: { heritage: Heritage }) {
    return (
        <>
            <Head title="My Heritage" />
            <MemberPageHeader
                eyebrow="Heritage No.001"
                title={`Edition No.${heritage.editionNumber}`}
                description="Your Founding Edition piece — status, delivery window, and the artefacts that travel with it."
            />

            <div className="grid gap-4 md:grid-cols-2">
                <MemberPanel>
                    <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                        Status
                    </p>
                    <p className="mt-2 font-serif text-[28px] text-cream">
                        {heritage.status}
                    </p>
                    <p className="mt-3 text-[14px] text-sand">
                        Delivery window · {heritage.deliveryWindow}
                    </p>
                </MemberPanel>
                <MemberPanel>
                    <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                        Artefacts
                    </p>
                    <ul className="mt-4 space-y-3 text-[15px] text-sand">
                        <li>Certificate · {heritage.certificate}</li>
                        <li>Passport · {heritage.passport}</li>
                    </ul>
                </MemberPanel>
            </div>
        </>
    );
}
