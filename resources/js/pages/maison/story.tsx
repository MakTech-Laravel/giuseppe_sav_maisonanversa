import { StoryCity } from '@/components/maison/story/story-city';
import { StoryClosing } from '@/components/maison/story/story-closing';
import { StoryFounder } from '@/components/maison/story/story-founder';
import { StoryHero } from '@/components/maison/story/story-hero';
import { StoryMake } from '@/components/maison/story/story-make';
import { StoryName } from '@/components/maison/story/story-name';
import { StoryOrigins } from '@/components/maison/story/story-origins';
import { StoryRitual } from '@/components/maison/story/story-ritual';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';

export default function Story() {
    return (
        <>
            <MaisonSeoHead />
            <StoryHero />
            <StoryCity />
            <StoryRitual />
            <StoryOrigins />
            <StoryName />
            <StoryMake />
            <StoryFounder />
            <StoryClosing />
        </>
    );
}
