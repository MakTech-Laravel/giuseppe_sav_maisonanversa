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

test('the founding circle portal persists the member in localStorage under the prototype key', function () {
    $source = file_get_contents(resource_path('js/lib/founding-circle.ts'));

    expect($source)
        ->toContain("FC_MEMBER_KEY = 'fc_member'")
        ->toContain('localStorage')
        ->toContain('readReferralFromSearch');
});

test('the circle portal builds crawlable referral links rather than hash navigation', function () {
    $source = file_get_contents(resource_path('js/components/maison/circle/circle-portal.tsx'));

    expect($source)
        ->toContain('maisonUrl')
        ->toContain('?ref=')
        ->toContain('navigator.clipboard')
        ->not->toContain('onclick=')
        ->not->toContain('alert(');
});

test('the circle portal sits as a dark card on the cream section', function () {
    $page = file_get_contents(resource_path('js/pages/maison/circle.tsx'));
    $portal = file_get_contents(resource_path('js/components/maison/circle/circle-portal.tsx'));

    expect($page)
        ->toContain('<CirclePortal />')
        ->toContain('openOrder')
        ->not->toContain('tone="choc2"')
        ->and($portal)
        ->toContain('bg-choc2')
        ->toContain('max-w-190')
        ->toContain('max-w-110');
});

test('dressing CTAs use shell actions or maison links', function () {
    $dressing = file_get_contents(resource_path('js/pages/maison/dressing.tsx'));

    expect($dressing)
        ->toContain('openNewsletter')
        ->toContain('MaisonLink')
        ->toContain('to="product"')
        ->not->toContain('onclick=');
});

test('the dressing page keeps the philosophy values and six collection pieces visible', function () {
    $source = file_get_contents(resource_path('js/pages/maison/dressing.tsx'));

    expect($source)
        ->toContain('Technische stoffen')
        ->toContain('Beperkte productie')
        ->toContain('Tijdloze palet')
        ->toContain('Padel Polo')
        ->toContain('Court Short')
        ->toContain('Warm-up Jacket')
        ->toContain('Court Cap')
        ->toContain('Sport Handdoek')
        ->toContain('Padel Grip')
        ->toContain('ma-lg:grid-cols-6')
        ->toContain('min-h-120')
        ->not->toContain('ma-lg:grid-cols-[1fr_1.1fr_1fr]');
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
        ->toContain('bg-cream p-10')
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
