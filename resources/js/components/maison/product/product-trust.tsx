import { SectionIcon } from '@/components/maison/product/section-icon';
import type { ProductSection } from '@/types/product';

export function ProductTrust({ section }: { section?: ProductSection }) {
    const items = section?.items ?? [];

    if (items.length === 0) {
        return null;
    }

    return (
        <div className="bg-choc">
            <div className="flex flex-wrap justify-center gap-x-14 gap-y-8 px-8 py-13">
                {items.map((item) => (
                    <div key={item.id} className="text-center">
                        {item.icon ? (
                            <div className="mb-2.5 text-[26px] text-gold">
                                <SectionIcon icon={item.icon} />
                            </div>
                        ) : null}
                        <div className="font-sans text-[9px] tracking-[0.2em] text-sand uppercase">
                            {item.title}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
