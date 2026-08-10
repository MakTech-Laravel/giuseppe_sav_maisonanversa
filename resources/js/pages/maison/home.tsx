import { Head } from '@inertiajs/react';
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
import type { Edition } from '@/types/edition';

export default function Home({ edition }: { edition: Edition }) {
    return (
        <>
            <Head title="Maison Anversa" />
            <HomeHero edition={edition} />
            <HomeMarquee />
            <HomeIntro />
            <HomeStory />
            <HomeAntwerp />
            <HomeProduct />
            <HomeUnboxing />
            <HomeCircle />
            <HomePreorder edition={edition} />
            <HomeContentGrid />
            <HomeManifesto />
            <HomeNewsletter />
        </>
    );
}
