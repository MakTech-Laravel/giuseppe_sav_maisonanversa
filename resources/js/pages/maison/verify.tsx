import { useTranslation } from 'react-i18next';
import { PageHero } from '@/components/maison/ui/page-hero';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Verify({
    piece,
}: {
    piece: {
        productName: string | null;
        editionTotal: number;
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
            <MaisonSeoHead />
            <PageHero
                eyebrow={piece.productName ?? t('Authenticiteit')}
                title={
                    <>
                        No.{piece.editionNumber}
                        {piece.editionTotal > 0 ? (
                            <em> / {piece.editionTotal}</em>
                        ) : null}
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
