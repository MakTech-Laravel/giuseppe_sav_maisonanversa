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
                <Head title={t('My Heritage')} />
                <MemberPageHeader
                    eyebrow={t('Heritage')}
                    title={t('My Heritage')}
                />
                <MemberEmptyState
                    title={t('No edition assigned yet')}
                    description={t(
                        'You are a Founding Circle member, but no Heritage edition is linked to your account yet. Contact the team for more information.',
                    )}
                />
            </>
        );
    }

    return (
        <>
            <Head title={t('My Heritage')} />
            <MemberPageHeader
                eyebrow={heritage.productName ?? t('Heritage')}
                title={t('Edition Nº {{number}}', {
                    number: heritage.editionNumber,
                })}
                description={t(
                    'Your Founding Edition piece — status, delivery window, and the artefacts that travel with it.',
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
                        {t('Delivery window · {{window}}', {
                            window: heritage.deliveryWindow,
                        })}
                    </p>
                </MemberPanel>
                <MemberPanel>
                    <p className="font-sans text-[9px] tracking-[0.22em] text-gold uppercase">
                        {t('Artefacts')}
                    </p>
                    <ul className="mt-4 space-y-3 text-[15px] text-sand">
                        <li>
                            {t('Certificate · {{value}}', {
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
