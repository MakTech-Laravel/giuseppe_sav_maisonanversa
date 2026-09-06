import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import {
    MemberEmptyState,
    MemberPageHeader,
    MemberPanel,
} from '@/components/member/member-ui';

type Heritage = {
    productName: string | null;
    editionTotal: number | null;
    editionNumber: string;
    status: string;
    deliveryWindow: string;
    certificate: string;
    passport: string;
};

export default function MemberHeritage({
    heritage,
}: {
    heritage: Heritage | null;
}) {
    const { t } = useTranslation();

    if (heritage === null) {
        return (
            <>
                <Head title={t('Mijn Heritage')} />
                <MemberPageHeader
                    eyebrow={t('Heritage')}
                    title={t('Mijn Heritage')}
                />
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
            <Head title={t('Mijn Heritage')} />
            <MemberPageHeader
                eyebrow={heritage.productName ?? t('Heritage')}
                title={t('Editie No.{{number}}', {
                    number: heritage.editionNumber,
                })}
                description={t(
                    'Uw Founding Edition-stuk — status, leveringsvenster en de artefacten die ermee meegaan.',
                )}
            />

            <div className="grid gap-4 md:grid-cols-2">
                <MemberPanel>
                    <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                        {t('Status')}
                    </p>
                    <p className="mt-2 font-serif text-[28px] text-cream">
                        {t(heritage.status)}
                    </p>
                    <p className="mt-3 text-[14px] text-sand">
                        {t('Leveringsvenster · {{window}}', {
                            window: heritage.deliveryWindow,
                        })}
                    </p>
                </MemberPanel>
                <MemberPanel>
                    <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                        {t('Artefacten')}
                    </p>
                    <ul className="mt-4 space-y-3 text-[15px] text-sand">
                        <li>
                            {t('Certificaat · {{value}}', {
                                value: heritage.certificate,
                            })}
                        </li>
                        <li>
                            {t('Passport · {{value}}', {
                                value: heritage.passport,
                            })}
                        </li>
                    </ul>
                </MemberPanel>
            </div>
        </>
    );
}
