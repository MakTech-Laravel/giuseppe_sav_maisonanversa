<?php

test('the community page renders the maison community component', function () {
    $this->get('/nl/community')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('maison/community'));
});

test('the feed composer renders user text safely without innerHTML', function () {
    $source = file_get_contents(resource_path('js/components/maison/community/feed-compose.tsx'));
    $postSource = file_get_contents(resource_path('js/components/maison/community/feed-post-card.tsx'));

    expect($source.$postSource)
        ->not->toContain('dangerouslySetInnerHTML')
        ->not->toContain('innerHTML');
});

test('community tabs use react state instead of switchCommTab onclick strings', function () {
    $tabsSource = file_get_contents(resource_path('js/components/maison/community/community-tabs.tsx'));
    $layoutSource = file_get_contents(resource_path('js/components/maison/community/community-layout.tsx'));

    expect($tabsSource.$layoutSource)
        ->toContain('useState')
        ->toContain('activeTab')
        ->not->toContain('switchCommTab');
});

test('a community toast helper exists for member feedback', function () {
    $source = file_get_contents(resource_path('js/components/maison/community/community-toast.tsx'));

    expect($source)
        ->toContain('useCommunityToast')
        ->toContain('CommunityToast');
});
