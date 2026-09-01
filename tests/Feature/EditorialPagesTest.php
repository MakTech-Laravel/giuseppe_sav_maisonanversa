<?php

test('story, circle, dressing and journal pages render their Inertia components', function (string $path, string $component) {
    $this->get($path)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['/nl/story', 'maison/story'],
    ['/nl/circle', 'maison/circle'],
    ['/nl/dressing', 'maison/dressing'],
    ['/nl/journal', 'maison/journal'],
]);

test('the circle portal points members to the authenticated card', function () {
    $source = file_get_contents(resource_path('js/components/maison/circle/circle-portal.tsx'));

    expect($source)
        ->toContain('openAuth')
        ->toContain('/member/circle')
        ->not->toContain('onclick=')
        ->not->toContain('alert(')
        ->not->toContain('localStorage');
});

test('the circle portal sits on the cream circle page', function () {
    $page = file_get_contents(resource_path('js/pages/maison/circle.tsx'));
    $portal = file_get_contents(resource_path('js/components/maison/circle/circle-portal.tsx'));

    expect($page)
        ->toContain('<CirclePortal />')
        ->toContain('openOrder')
        ->not->toContain('tone="choc2"')
        ->and($portal)
        ->toContain('bg-cream2')
        ->toContain('border-gold/25');
});

test('dressing CTAs use shell actions or maison links', function () {
    $dressing = file_get_contents(resource_path('js/pages/maison/dressing.tsx'));

    expect($dressing)
        ->toContain('openNewsletter')
        ->toContain('MaisonLink')
        ->toContain('foundingProductUrl')
        ->not->toContain('onclick=');
});

test('the dressing page keeps the philosophy values and six collection pieces visible', function () {
    $source = file_get_contents(resource_path('js/pages/maison/dressing.tsx'));

    expect($source)
        ->toContain('Technische stoffen')
        ->toContain('Beperkte productie')
        ->toContain('Tijdloze palet')
        ->toContain('ma-lg:grid-cols-6')
        ->toContain('min-h-120')
        ->not->toContain('ma-lg:grid-cols-[1fr_1.1fr_1fr]');

    $this->get('/nl/dressing')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('items', 6)
            ->where('items.0.name', 'Padel Polo')
            ->where('items.5.name', 'Padel Grip'));
});

test('the dressing founding-circle heading translates ziet into English', function () {
    $english = json_decode((string) file_get_contents(lang_path('en.json')), true);

    expect($english['die het'])->toBe('to')
        ->and($english['ziet'])->toBe('see it')
        ->and($english['Wees de eerste'])->toBe('Be the first');
});

test('the circle benefits grid matches the prototype hairline layout', function () {
    $source = file_get_contents(resource_path('js/pages/maison/circle.tsx'));

    expect($source)
        ->toContain('bg-sand')
        ->toContain('gap-0.5')
        ->toContain('bg-cream p-6 md:p-10')
        ->toContain('text-[80px]')
        ->toContain('text-gold/4')
        ->toContain('text-[36px]')
        ->toContain('text-gold/25')
        ->not->toContain('bg-cream2')
        ->not->toContain('border border-gold/15');
});

test('the journal cards cover the six prototype articles', function () {
    $source = file_get_contents(resource_path('js/pages/maison/journal.tsx'));

    expect($source)
        ->toContain('JournalCard')
        ->toContain('JournalPagination')
        ->toContain('articles.data.map');
});

test('the story page keeps the founder quote as english brand copy', function () {
    $source = file_get_contents(resource_path('js/pages/maison/story.tsx'));

    expect($source)
        ->toContain('Heritage is not what we inherit')
        ->toContain('Yusuf Savran')
        ->not->toContain('onclick=');
});
