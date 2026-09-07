import { useTranslation } from 'react-i18next';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { PageHero } from '@/components/maison/ui/page-hero';

type Holder = {
    name: string;
    username: string | null;
    isFoundingCircle: boolean;
    memberSince: string | null;
    source: 'order' | 'claim';
};

type Piece = {
    productName: string | null;
    editionTotal: number;
    editionNumber: string;
    status: string;
    statusLabel: string;
    allocatedAt: string | null;
    holder: Holder | null;
};

export default function Verify({ piece }: { piece: Piece }) {
    const { t } = useTranslation();
    const isArchive = piece.status === 'archive';
    const holder = piece.holder;

    return (
        <>
            <MaisonSeoHead />
            <PageHero
                eyebrow={piece.productName ?? t('Authenticiteit')}
                title={
                    <>
                        {t('Nr. {{number}}', { number: piece.editionNumber })}
                        {piece.editionTotal > 0 ? (
                            <em> / {piece.editionTotal}</em>
                        ) : null}
                    </>
                }
                subtitle={
                    isArchive
                        ? t('Maison Anversa Archive — niet te koop')
                        : holder
                          ? t('Geverifieerd Founding Edition-stuk')
                          : t('Geverifieerd door Maison Anversa')
                }
            />

            <section className="mx-auto max-w-xl px-6 pb-24 text-center text-choc3">
                {holder ? (
                    <div className="border border-gold/25 bg-cream/40 px-6 py-8">
                        <p className="font-sans text-[10px] tracking-[0.22em] text-gold uppercase">
                            {t('Houder')}
                        </p>
                        <p className="mt-3 font-serif text-3xl text-choc">
                            {holder.name}
                        </p>
                        {holder.username && (
                            <p className="mt-1 font-sans text-sm text-choc3">
                                @{holder.username}
                            </p>
                        )}

                        <dl className="mt-6 space-y-3 text-left">
                            {holder.isFoundingCircle && (
                                <div className="flex items-baseline justify-between gap-4 border-t border-gold/15 pt-3">
                                    <dt className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase">
                                        {t('Lidmaatschap')}
                                    </dt>
                                    <dd className="font-sans text-sm text-choc">
                                        {holder.memberSince
                                            ? t(
                                                  'Founding Circle · sinds {{since}}',
                                                  {
                                                      since: holder.memberSince,
                                                  },
                                              )
                                            : t('Founding Circle-lid')}
                                    </dd>
                                </div>
                            )}

                            <div className="flex items-baseline justify-between gap-4 border-t border-gold/15 pt-3">
                                <dt className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase">
                                    {t('Status')}
                                </dt>
                                <dd className="font-sans text-sm text-choc">
                                    {t(piece.statusLabel)}
                                </dd>
                            </div>

                            {(piece.allocatedAt || holder.memberSince) && (
                                <div className="flex items-baseline justify-between gap-4 border-t border-gold/15 pt-3">
                                    <dt className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase">
                                        {t('Toegewezen')}
                                    </dt>
                                    <dd className="font-sans text-sm text-choc">
                                        {piece.allocatedAt ?? holder.memberSince}
                                    </dd>
                                </div>
                            )}

                            {piece.productName && (
                                <div className="flex items-baseline justify-between gap-4 border-t border-gold/15 pt-3">
                                    <dt className="font-sans text-[10px] tracking-[0.18em] text-gold uppercase">
                                        {t('Product')}
                                    </dt>
                                    <dd className="font-sans text-sm text-choc">
                                        {piece.productName}
                                    </dd>
                                </div>
                            )}
                        </dl>

                        <p className="mt-8 font-sans text-[11px] tracking-[0.14em] text-gold uppercase">
                            {t('Authenticiteit gegarandeerd')}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <p className="font-serif text-2xl text-choc">
                            {isArchive
                                ? t('Maison Anversa Archive — niet te koop')
                                : t('Geen houder geregistreerd')}
                        </p>
                        <p className="text-sm">
                            {t('Status')}: {t(piece.statusLabel)}
                        </p>
                        {piece.allocatedAt && (
                            <p className="text-sm">
                                {t('Toegewezen')} {piece.allocatedAt}
                            </p>
                        )}
                    </div>
                )}
            </section>
        </>
    );
}
