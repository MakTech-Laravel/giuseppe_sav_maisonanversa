import { StoryCity } from '@/components/maison/story/story-city';
import { StoryClosing } from '@/components/maison/story/story-closing';
import { StoryFounder } from '@/components/maison/story/story-founder';
import { StoryHero } from '@/components/maison/story/story-hero';
import { StoryMake } from '@/components/maison/story/story-make';
import { StoryName } from '@/components/maison/story/story-name';
import { StoryOrigins } from '@/components/maison/story/story-origins';
import { StoryQuote } from '@/components/maison/story/story-quote';
import { StoryRitual } from '@/components/maison/story/story-ritual';
import { MaisonSeoHead } from '@/components/maison/seo/maison-seo-head';
import type { StoryPageContent } from '@/types/story-page';

export default function Story({ story }: { story: StoryPageContent }) {
    return (
        <>
            <MaisonSeoHead />
            <StoryHero hero={story.hero} />
            <StoryCity city={story.city} />
            <StoryRitual ritual={story.ritual} />
            <StoryOrigins origins={story.origins} />
            <StoryName name={story.name} />
            <StoryMake make={story.make} />
            <StoryQuote quote={story.quote} />
            <StoryFounder founder={story.founder} />
            <StoryClosing closing={story.closing} />
        </>
    );
}
