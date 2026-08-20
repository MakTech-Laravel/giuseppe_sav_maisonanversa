import { useTranslation } from 'react-i18next';
import { MaisonLink } from '@/components/maison/maison-link';
import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { MaisonButton } from '@/components/maison/ui/maison-button';
import { Reveal } from '@/components/maison/ui/reveal';
import { Section, Wrap } from '@/components/maison/ui/section';

type HomeProductData = {
    materials?: string[];
    trust_badges?: string[];
};

export function HomeProduct({ product }: { product?: HomeProductData | null }) {
    const { t } = useTranslation();
    const specs = [
        ...(product?.materials ?? []),
        ...(product?.trust_badges ?? []),
    ];
    const fallbackSpecs = [
        'Full Carbon Frame — 3K weave',
        'Premium 3K Carbon Surface',
        'Echte Lederen Greep',
        'Heritage Certificaat + Paspoort',
        'Founding Circle uitnodiging',
        'Premium verpakking',
    ];
    const list = specs.length > 0 ? specs : fallbackSpecs;

    return (
        <Section tone="cream2">
            <Wrap>
                <div className="grid items-center gap-10 ma-md:grid-cols-2 ma-md:gap-12 ma-lg:grid-cols-[1fr_1.3fr_1fr] ma-lg:gap-15">
                    <Reveal>
                        <Eyebrow tone="gold2">Heritage No.001</Eyebrow>
                        <h2 className="mt-3 mb-4 font-serif text-[clamp(28px,3.5vw,46px)] leading-[1.15] font-medium tracking-[0.06em] uppercase">
                            {t('Het Eerste.')}
                            <br />
                            {t('Het Originele.')}
                            <br />
                            {t('Het Uwe.')}
                        </h2>
                        <p className="mb-5 text-[15px] leading-[1.8] text-choc3">
                            {t(
                                'The Founding Edition. Beperkt tot 100 stuks wereldwijd. Elk vergezeld van een Heritage Certificaat, Paspoort en een persoonlijke brief.',
                            )}
                        </p>
                        <ul className="mb-8 flex flex-col gap-2">
                            {list.map((spec) => (
                                <li
                                    key={spec}
                                    className="flex items-center gap-2.5 font-sans text-[13px] text-choc3 before:text-[20px] before:leading-0 before:text-gold2 before:content-['·']"
                                >
                                    {t(spec)}
                                </li>
                            ))}
                        </ul>
                        <MaisonButton
                            as={MaisonLink}
                            variant="choc"
                            to="product"
                        >
                            {t('Ontdek het Racket →')}
                        </MaisonButton>
                    </Reveal>

                    <Reveal className="relative aspect-3/4 w-full overflow-hidden">
                        <PlaceholderImage
                            asset="heritage-001-front"
                            ratio={null}
                            alt="Heritage No.001"
                            captioned={false}
                            overlay="linear-gradient(to top, rgba(41,28,24,0.55) 0%, rgba(41,28,24,0.05) 45%, rgba(41,28,24,0.05) 100%)"
                            className="absolute inset-0 h-full w-full [&_img]:object-cover [&_img]:object-center"
                        />
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_40%_35%,rgba(141,112,90,0.05)_0%,transparent_60%)]"
                        />
                        <div className="absolute top-6 right-6 text-right font-serif text-[10px] tracking-[0.2em] text-gold/55 uppercase">
                            Heritage No.001
                            <br />
                            Founding Edition
                        </div>
                        <div className="absolute bottom-6 left-6 flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center overflow-hidden border border-gold">
                                <PlaceholderImage
                                    asset="logo-icon"
                                    ratio="1 / 1"
                                    alt=""
                                    captioned={false}
                                    className="size-full"
                                />
                            </div>
                            <p className="font-sans text-[7px] leading-[1.8] tracking-[0.18em] text-sand uppercase">
                                Maison Anversa
                                <br />
                                Antwerp · Belgium
                            </p>
                        </div>
                    </Reveal>

                    <Reveal className="ma-md:col-span-2 ma-lg:col-span-1">
                        <div className="mx-auto w-full max-w-70 border border-gold/25 px-6 py-8 text-center ma-lg:mx-0 ma-lg:max-w-none">
                            <div className="mb-1.5 font-serif text-[68px] leading-none font-light text-gold2 lining-nums">
                                100
                            </div>
                            <div className="font-sans text-[8px] font-light tracking-[0.28em] text-choc3 uppercase">
                                {t('Stuks Alleen')}
                                <br />
                                {t('Wereldwijd')}
                            </div>
                            <div className="mx-auto my-3.5 h-px w-7 bg-gold/30" />
                            <div className="font-sans text-[8px] leading-[2] font-light tracking-[0.2em] text-choc3 uppercase">
                                {t('Elk stuk individueel')}
                                <br />
                                {t('genummerd')}
                            </div>
                            <div className="mt-3.5 font-serif text-lg font-medium tracking-[0.1em] text-choc lining-nums">
                                001 / 100
                            </div>
                        </div>
                    </Reveal>
                </div>
            </Wrap>
        </Section>
    );
}
