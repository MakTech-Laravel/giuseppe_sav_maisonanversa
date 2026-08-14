import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { PageHero } from '@/components/maison/ui/page-hero';

export default function Verify({
    piece,
}: {
    piece: {
        editionNumber: string;
        status: string;
        notes: string | null;
        allocatedAt: string | null;
        owner: string | null;
    };
}) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Authenticiteit')}>
                <meta name="robots" content="noindex, nofollow" />
            </Head>
            <PageHero
                eyebrow={t('Heritage No.001')}
                title={
                    <>
                        No.{piece.editionNumber} <em>/ 100</em>
                    </>
                }
                subtitle={
                    piece.status === 'archive'
                        ? t('Maison Anversa Archive — niet te koop')
                        : t('Geverifieerd Founding Edition-stuk')
                }
            />
            <section className="mx-auto max-w-xl px-6 pb-24 text-center text-choc3">
                {piece.owner && (
                    <p className="font-serif text-2xl text-choc">{piece.owner}</p>
                )}
                {piece.allocatedAt && (
                    <p className="mt-2 text-sm">
                        {t('Toegewezen')} {piece.allocatedAt}
                    </p>
                )}
                {piece.notes && <p className="mt-4 text-sm">{piece.notes}</p>}
            </section>
        </>
    );
}
