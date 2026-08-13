import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { MemberPageHeader, MemberPanel } from '@/components/member/member-ui';

type PassportPage = { title: string; body: string };

export default function MemberPassport({
    passport,
}: {
    passport: { editionNumber: string; pages: PassportPage[] };
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Digitaal Heritage Passport')} />
            <MemberPageHeader
                eyebrow={`No.${passport.editionNumber}`}
                title={t('Digitaal Heritage Passport')}
                description={t(
                    'Vier pagina\'s van het fysieke passport — een leesbare kopie tot de editie verzonden wordt.',
                )}
            />

            <div className="grid gap-4 md:grid-cols-2">
                {passport.pages.map((page, index) => (
                    <MemberPanel key={page.title} className="min-h-48 bg-choc">
                        <p className="font-sans text-[9px] tracking-[0.25em] text-gold uppercase">
                            {t('Pagina {{number}}', {
                                number: String(index + 1).padStart(2, '0'),
                            })}
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
