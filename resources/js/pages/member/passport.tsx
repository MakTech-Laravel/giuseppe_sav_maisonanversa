import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import PassportPdfController from '@/actions/App/Http/Controllers/Member/PassportPdfController';
import {
    MemberEmptyState,
    MemberPageHeader,
    MemberPanel,
} from '@/components/member/member-ui';
import { wayfinderLocale } from '@/lib/wayfinder-defaults';

type PassportPage = { title: string; body: string };

export default function MemberPassport({
    passport,
}: {
    passport: {
        editionNumber: string;
        pages: PassportPage[];
        verificationUrl?: string;
    } | null;
}) {
    const { t } = useTranslation();

    if (passport === null) {
        return (
            <>
                <Head title={t('Digitaal Heritage Passport')} />
                <MemberPageHeader title={t('Digitaal Heritage Passport')} />
                <MemberEmptyState
                    title={t('Nog geen editie toegewezen')}
                    description={t(
                        'U bent lid van de Founding Circle, maar er is nog geen Heritage-editie aan uw account gekoppeld. Neem contact op met het team voor meer informatie.',
                    )}
                />
            </>
        );
    }

    return (
        <>
            <Head title={t('Digitaal Heritage Passport')} />
            <MemberPageHeader
                eyebrow={`No.${passport.editionNumber}`}
                title={t('Digitaal Heritage Passport')}
                description={t(
                    "Vier pagina's van het fysieke passport — een leesbare kopie tot de editie verzonden wordt.",
                )}
            />

            <div className="mb-8">
                <a
                    href={PassportPdfController.url(wayfinderLocale())}
                    className="inline-flex items-center gap-2 border border-gold/45 bg-choc3 px-4 py-2.5 font-sans text-[11px] tracking-[0.16em] text-cream uppercase no-underline transition-colors hover:border-gold hover:text-gold"
                >
                    <Download className="size-3.5" aria-hidden />
                    {t('Download PDF')}
                </a>
            </div>

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
                        {page.title === passport.pages[3]?.title &&
                            passport.verificationUrl && (
                                <p className="mt-4 font-sans text-[11px] break-all text-gold">
                                    {passport.verificationUrl}
                                </p>
                            )}
                    </MemberPanel>
                ))}
            </div>
        </>
    );
}
