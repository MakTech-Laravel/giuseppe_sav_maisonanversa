import { HomeAntwerp } from '@/components/maison/home/home-antwerp';
import { HomeCircle } from '@/components/maison/home/home-circle';
import { HomeContentGrid } from '@/components/maison/home/home-content-grid';
import { HomeHero } from '@/components/maison/home/home-hero';
import { HomeIntro } from '@/components/maison/home/home-intro';
import { HomeManifesto } from '@/components/maison/home/home-manifesto';
import { HomeMarquee } from '@/components/maison/home/home-marquee';
import { HomeNewsletter } from '@/components/maison/home/home-newsletter';
import { HomePreorder } from '@/components/maison/home/home-preorder';
import { HomeProduct } from '@/components/maison/home/home-product';
import { HomeStory } from '@/components/maison/home/home-story';
import { HomeUnboxing } from '@/components/maison/home/home-unboxing';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import type { Edition } from '@/types/edition';

type HomeProductData = {
    materials?: string[];
    trust_badges?: string[];
    includes?: string[];
};

export default function Home({
    edition,
    product,
}: {
    edition: Edition;
    product?: HomeProductData | null;
}) {
    return (
        <>
            <MaisonSeoHead />
            <HomeHero edition={edition} />
            <HomeMarquee />
            <HomeIntro />
            <HomeStory />
            <HomeAntwerp />
            <HomeProduct product={product} />
            <HomeUnboxing />
            <HomeCircle />
            <HomePreorder edition={edition} includes={product?.includes} />
            <HomeContentGrid />
            <HomeManifesto />
            <HomeNewsletter />
        </>
    );
}
