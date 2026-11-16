import { Wrap } from '@/components/maison/ui/section';

export function HomeManifesto() {
    return (
        <div className="border-t border-gold/12 bg-choc py-18 text-center text-cream">
            <Wrap>
                <p className="mx-auto max-w-190 font-serif text-[clamp(17px,2vw,22px)] leading-[2.1] text-cream">
                    <strong className="font-normal text-gold italic">
                        Every legacy begins with a first chapter.
                    </strong>
                </p>
            </Wrap>
        </div>
    );
}
