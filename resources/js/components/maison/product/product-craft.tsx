import { PlaceholderImage } from '@/components/maison/placeholder-image';
import { Eyebrow } from '@/components/maison/ui/eyebrow';
import { GoldRule } from '@/components/maison/ui/gold-rule';
import { Reveal } from '@/components/maison/ui/reveal';
import type { ImageAssetName } from '@/lib/imagery';
import { IMAGE_ASSETS } from '@/lib/imagery';
import type { ProductSection } from '@/types/product';

const CLASS_NAME = 'min-h-[300px] md:min-h-[440px]';
const OVERLAY = 'linear-gradient(to top, rgba(41,28,24,0.4), transparent)';

function CraftImage({ src, alt }: { src: string | null; alt: string }) {
    if (src && !(src in IMAGE_ASSETS)) {
        return (
            <div className={`relative overflow-hidden ${CLASS_NAME}`}>
                <img
                    src={src}
                    alt={alt}
                    className="absolute inset-0 h-full w-full object-cover"
                />
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0"
                    style={{ backgroundImage: OVERLAY }}
                />
            </div>
        );
    }

    return (
        <PlaceholderImage
            asset={(src ?? 'atelier-workshop') as ImageAssetName}
            ratio={null}
            alt={alt}
            captioned={false}
            overlay={OVERLAY}
            className={CLASS_NAME}
        />
    );
}

export function ProductCraft({ section }: { section?: ProductSection }) {
    const items = section?.items ?? [];

    if (items.length === 0) {
        return null;
    }

    return (
        <section className="bg-choc text-cream">
            <div className="grid md:grid-cols-2">
                <CraftImage
                    src={section?.image ?? null}
                    alt={section?.eyebrow || section?.heading || ''}
                />

                <Reveal className="flex flex-col justify-center px-8 py-13 md:px-16 md:py-20">
                    {section?.eyebrow ? (
                        <Eyebrow>{section.eyebrow}</Eyebrow>
                    ) : null}
                    <GoldRule />
                    {section?.heading || section?.subheading ? (
                        <h2 className="mb-5.5 font-serif text-[clamp(30px,3.4vw,46px)] leading-[1.1] font-normal tracking-[0.03em] uppercase">
                            {section.heading}
                            {section.subheading ? (
                                <>
                                    <br />
                                    {section.subheading}
                                </>
                            ) : null}
                        </h2>
                    ) : null}
                    {section?.intro ? (
                        <p className="text-[15px] leading-[1.85] text-sand">
                            {section.intro}
                        </p>
                    ) : null}

                    <div className="mt-10 grid grid-cols-1 gap-0.5 bg-gold/15 sm:grid-cols-2">
                        {items.map((item) => (
                            <div key={item.id} className="bg-choc2 px-6.5 py-7.5">
                                {item.number_label ? (
                                    <div className="font-serif text-[13px] tracking-[0.2em] text-gold">
                                        {item.number_label}
                                    </div>
                                ) : null}
                                <div className="my-2 font-serif text-[21px] text-cream">
                                    {item.title}
                                </div>
                                <div className="text-[13px] leading-[1.7] text-sand">
                                    {item.body}
                                </div>
                            </div>
                        ))}
                    </div>
                </Reveal>
            </div>
        </section>
    );
}
