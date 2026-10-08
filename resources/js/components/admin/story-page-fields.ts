export type StoryField = {
    key: string;
    label: string;
    long?: boolean;
    translated?: boolean;
};

export type StorySectionFields = {
    key: string;
    title: string;
    visible: string;
    fields: StoryField[];
};

export const STORY_SECTIONS: StorySectionFields[] = [
    {
        key: 'hero',
        title: 'Hero',
        visible: 'hero_visible',
        fields: [
            { key: 'hero_eyebrow', label: 'Wenkbrauw' },
            { key: 'hero_title', label: 'Titel' },
            { key: 'hero_title_accent', label: 'Gouden accent' },
            { key: 'hero_body', label: 'Tekst', long: true },
        ],
    },
    {
        key: 'city',
        title: 'De stad',
        visible: 'city_visible',
        fields: [
            { key: 'city_eyebrow', label: 'Wenkbrauw' },
            { key: 'city_title', label: 'Titel' },
            { key: 'city_body_one', label: 'Eerste alinea', long: true },
            { key: 'city_body_two', label: 'Tweede alinea', long: true },
            { key: 'city_closer', label: 'Slotzin', long: true },
        ],
    },
    {
        key: 'ritual',
        title: 'Het ritueel',
        visible: 'ritual_visible',
        fields: [
            { key: 'ritual_eyebrow', label: 'Wenkbrauw' },
            { key: 'ritual_title', label: 'Titel' },
            { key: 'ritual_step_1_title', label: 'Stap 01 titel' },
            { key: 'ritual_step_1_body', label: 'Stap 01 tekst', long: true },
            { key: 'ritual_step_2_title', label: 'Stap 02 titel' },
            { key: 'ritual_step_2_body', label: 'Stap 02 tekst', long: true },
            { key: 'ritual_step_3_title', label: 'Stap 03 titel' },
            { key: 'ritual_step_3_body', label: 'Stap 03 tekst', long: true },
            { key: 'ritual_step_4_title', label: 'Stap 04 titel' },
            { key: 'ritual_step_4_body', label: 'Stap 04 tekst', long: true },
            { key: 'ritual_footer', label: 'Tekst onderaan', long: true },
        ],
    },
    {
        key: 'origins',
        title: 'Hoe het begon',
        visible: 'origins_visible',
        fields: [
            { key: 'origins_eyebrow', label: 'Wenkbrauw' },
            { key: 'origins_lead', label: 'Inleiding', long: true },
            { key: 'origins_title', label: 'Titel' },
            { key: 'origins_body', label: 'Tekst', long: true },
            { key: 'origins_italic', label: 'Cursieve zin', long: true },
            { key: 'origins_close', label: 'Slot', long: true },
        ],
    },
    {
        key: 'name',
        title: 'De naam',
        visible: 'name_visible',
        fields: [
            { key: 'name_eyebrow', label: 'Wenkbrauw' },
            { key: 'name_title', label: 'Naam', translated: false },
            { key: 'name_pronunciation', label: 'Uitspraak', translated: false },
            { key: 'name_body', label: 'Tekst', long: true },
        ],
    },
    {
        key: 'make',
        title: 'Wat we maken',
        visible: 'make_visible',
        fields: [
            { key: 'make_eyebrow', label: 'Wenkbrauw' },
            { key: 'make_title', label: 'Productnaam', translated: false },
            { key: 'make_body_one', label: 'Eerste alinea', long: true },
            { key: 'make_body_two', label: 'Tweede alinea', long: true },
            { key: 'make_button_label', label: 'Knop' },
            { key: 'make_caption_one', label: 'Bijschrift 1' },
            { key: 'make_caption_two', label: 'Bijschrift 2' },
            { key: 'make_caption_three', label: 'Bijschrift 3' },
        ],
    },
    {
        key: 'quote',
        title: 'Citaat',
        visible: 'quote_visible',
        fields: [{ key: 'quote_line', label: 'Citaat', long: true }],
    },
    {
        key: 'founder',
        title: 'De oprichter',
        visible: 'founder_visible',
        fields: [
            { key: 'founder_eyebrow', label: 'Wenkbrauw' },
            { key: 'founder_name', label: 'Naam', translated: false },
            { key: 'founder_role', label: 'Rol' },
            { key: 'founder_paragraph_one', label: 'Alinea 1', long: true },
            { key: 'founder_paragraph_two', label: 'Alinea 2', long: true },
            { key: 'founder_paragraph_three', label: 'Alinea 3', long: true },
            { key: 'founder_paragraph_four', label: 'Alinea 4', long: true },
            { key: 'founder_paragraph_five', label: 'Alinea 5', long: true },
            { key: 'founder_paragraph_six', label: 'Alinea 6', long: true },
            { key: 'founder_signature', label: 'Handtekening', translated: false },
        ],
    },
    {
        key: 'closing',
        title: 'Slot',
        visible: 'closing_visible',
        fields: [
            { key: 'closing_line', label: 'Slotzin', long: true },
            { key: 'closing_place', label: 'Plaats en jaar' },
        ],
    },
];

export const STORY_TRANSLATED_FIELDS = STORY_SECTIONS.flatMap((section) =>
    section.fields.filter((field) => field.translated !== false),
);

export type StoryImageSlot = {
    slot: string;
    section: string;
    label: string;
};

export const STORY_IMAGES: StoryImageSlot[] = [
    { slot: 'hero', section: 'hero', label: 'Foto' },
    { slot: 'city', section: 'city', label: 'Foto' },
    { slot: 'name', section: 'name', label: 'Foto' },
    { slot: 'make_one', section: 'make', label: 'Foto 1' },
    { slot: 'make_two', section: 'make', label: 'Foto 2' },
    { slot: 'make_three', section: 'make', label: 'Foto 3' },
    { slot: 'founder', section: 'founder', label: 'Foto' },
];
