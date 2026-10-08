export type StoryHeroContent = {
    eyebrow: string;
    title: string;
    titleAccent: string;
    body: string;
    imageUrl: string | null;
};

export type StoryCityContent = {
    eyebrow: string;
    title: string;
    bodyOne: string;
    bodyTwo: string;
    closer: string;
    imageUrl: string | null;
};

export type StoryRitualContent = {
    eyebrow: string;
    title: string;
    steps: Array<{ number: string; title: string; body: string }>;
    footer: string;
};

export type StoryOriginsContent = {
    eyebrow: string;
    lead: string;
    title: string;
    body: string;
    italic: string;
    close: string;
};

export type StoryNameContent = {
    eyebrow: string;
    title: string;
    pronunciation: string;
    body: string;
    imageUrl: string | null;
};

export type StoryMakeContent = {
    eyebrow: string;
    title: string;
    bodyOne: string;
    bodyTwo: string;
    buttonLabel: string;
    buttonHref: string;
    captions: string[];
    imageUrls: Array<string | null>;
};

export type StoryQuoteContent = {
    line: string;
};

export type StoryFounderContent = {
    eyebrow: string;
    name: string;
    role: string;
    paragraphs: string[];
    signature: string;
    imageUrl: string | null;
};

export type StoryClosingContent = {
    line: string;
    place: string;
};

export type StoryPageContent = {
    hero: StoryHeroContent | null;
    city: StoryCityContent | null;
    ritual: StoryRitualContent | null;
    origins: StoryOriginsContent | null;
    name: StoryNameContent | null;
    make: StoryMakeContent | null;
    quote: StoryQuoteContent | null;
    founder: StoryFounderContent | null;
    closing: StoryClosingContent | null;
};
