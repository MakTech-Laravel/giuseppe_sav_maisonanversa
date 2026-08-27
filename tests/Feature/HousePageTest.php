<?php

test('the house page renders eight crawlable room links', function () {
    $this->get('/nl/huis')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('maison/house'));

    $source = file_get_contents(resource_path('js/components/maison/house/maison-floorplan.tsx'));
    $list = file_get_contents(resource_path('js/components/maison/house/maison-room-list.tsx'));

    foreach (['home', 'contact', 'story', 'community', 'circle', 'journal', 'corner'] as $page) {
        expect($source)->toContain("to=\"{$page}\"")
            ->and($list)->toContain("to: '{$page}'");
    }

    expect($source)->toContain('foundingProductUrl')
        ->and($list)->toContain('foundingProductUrl');
});

test('rooms are SVG anchors rather than onclick handlers on groups', function () {
    $source = file_get_contents(resource_path('js/components/maison/house/floorplan-room.tsx'));

    expect($source)
        ->toContain('<a href={href}')
        ->toContain('usePageTransition')
        ->not->toContain('onclick=');
});

test('the mobile room list is the only entry below 640px', function () {
    $page = file_get_contents(resource_path('js/pages/maison/house.tsx'));
    $list = file_get_contents(resource_path('js/components/maison/house/maison-room-list.tsx'));

    expect($page)->toContain('max-[640px]:hidden')
        ->and($list)->toContain('min-[641px]:hidden');
});
