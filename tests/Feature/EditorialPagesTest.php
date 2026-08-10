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

test('dressing and circle CTAs use shell actions or maison links', function () {
    $dressing = file_get_contents(resource_path('js/pages/maison/dressing.tsx'));
    $circle = file_get_contents(resource_path('js/pages/maison/circle.tsx'));

    expect($dressing)
        ->toContain('openNewsletter')
        ->toContain('MaisonLink')
        ->toContain("to=\"product\"")
        ->not->toContain('onclick=')
        ->and($circle)
        ->toContain('openOrder')
        ->not->toContain('onclick=');
});

test('the journal cards cover the six prototype articles', function () {
    $source = file_get_contents(resource_path('js/pages/maison/journal.tsx'));

    foreach ([
        'antwerp-cityscape',
        'heritage-001-lifestyle-court',
        'atelier-workshop',
        'heritage-001-detail-gravure',
        'heritage-001-front',
        'hero-mansion',
    ] as $asset) {
        expect($source)->toContain("'{$asset}'");
    }
});

test('the story page keeps the founder quote as english brand copy', function () {
    $source = file_get_contents(resource_path('js/pages/maison/story.tsx'));

    expect($source)
        ->toContain('Heritage is not what we inherit')
        ->toContain('Yusuf Savran')
        ->not->toContain('onclick=');
});
