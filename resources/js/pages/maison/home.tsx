import { HomeAntwerp } from '@/components/maison/home/home-antwerp';
import { HomeCircle } from '@/components/maison/home/home-circle';
import { HomeContentGrid } from '@/components/maison/home/home-content-grid';
import { HomeHero } from '@/components/maison/home/home-hero';
import type { HomeHeroContent } from '@/components/maison/home/home-hero';
import { HomeIntro } from '@/components/maison/home/home-intro';
import { HomeManifesto } from '@/components/maison/home/home-manifesto';
import { HomeMarquee } from '@/components/maison/home/home-marquee';
import { HomeNewsletter } from '@/components/maison/home/home-newsletter';
import { HomePreorder } from '@/components/maison/home/home-preorder';
import { HomeProduct } from '@/components/maison/home/home-product';
import { HomeRegister } from '@/components/maison/home/home-register';
import { HomeSessions } from '@/components/maison/home/home-sessions';
import { HomeStory } from '@/components/maison/home/home-story';
import { HomeUnboxing } from '@/components/maison/home/home-unboxing';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import type { Edition } from '@/types/edition';
import type { SessionCard } from '@/types/session';

type HomeProductData = {
    materials?: Array<{ name: string } | string>;
    trust_badges?: Array<{ text: string } | string>;
    includes?: string[];
};

export default function Home({
    edition,
    product,
    hero,
    upcomingSessions = [],
    openSessionsThisWeek = 0,
}: {
    edition: Edition;
    product?: HomeProductData | null;
    hero: HomeHeroContent;
    upcomingSessions?: SessionCard[];
    openSessionsThisWeek?: number;
}) {
    return (
        <>
            <MaisonSeoHead />
            <HomeHero edition={edition} hero={hero} />
            <HomeMarquee />
            <HomeIntro />
            <HomeStory />
            <HomeSessions
                sessions={upcomingSessions}
                openSessionsThisWeek={openSessionsThisWeek}
            />
            <HomeAntwerp />
            <HomeProduct product={product} />
            <HomeRegister />
            <HomeUnboxing />
            <HomeCircle />
            <HomePreorder edition={edition} includes={product?.includes} />
            <HomeContentGrid />
            <HomeManifesto />
            <HomeNewsletter />
        </>
    );
}
