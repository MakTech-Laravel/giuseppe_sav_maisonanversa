import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import { MaisonFloorplan } from '@/components/maison/house/maison-floorplan';
import { MaisonRoomList } from '@/components/maison/house/maison-room-list';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Section, Wrap } from '@/components/maison/ui/section';

export default function House() {
    const { t } = useTranslation();

    return (
        <>
            <MaisonSeoHead page="house" />

            <Section tone="dark" className="text-center">
                <Wrap>
                    <PlaceholderImage
                        asset="maison-facade"
                        ratio={null}
                        alt="Maison Anversa"
                        captioned={false}
                        className="mx-auto mb-1.5 w-[min(340px,72vw)] opacity-94 drop-shadow-[0_6px_30px_rgba(0,0,0,0.4)]"
                    />
                    <Eyebrow className="mt-3.5">{t('Het Huis')}</Eyebrow>
                    <GoldRule center className="mx-auto" />
                    <p className="mx-auto mt-5 max-w-160 text-[15px] leading-[1.85] text-sand">
                        {t(
                            'Acht kamers, acht hoofdstukken. Stap binnen door op een ruimte te klikken — elke kamer brengt u naar een deel van het huis.',
                        )}
                    </p>
                </Wrap>
            </Section>

            <Section tone="dark" padded={false} className="pb-22.5">
                <Wrap className="max-w-260">
                    <div className="overflow-x-auto pb-2 max-[640px]:hidden">
                        <MaisonFloorplan />
                    </div>

                    <MaisonRoomList />

                    <div className="mt-10 flex flex-wrap justify-center gap-x-7 gap-y-3.5">
                        <LegendItem>
                            {t('Klik een kamer om binnen te treden')}
                        </LegendItem>
                        <LegendItem>{t('8 kamers · 1 huis')}</LegendItem>
                    </div>
                </Wrap>
            </Section>
        </>
    );
}

function LegendItem({ children }: { children: string }) {
    return (
        <div className="flex items-center gap-2.5 font-sans text-[9px] tracking-[0.2em] text-sand uppercase">
            <span
                aria-hidden="true"
                className="size-2 rotate-45 border border-gold"
            />
            {children}
        </div>
    );
}
