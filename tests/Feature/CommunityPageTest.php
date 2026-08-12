<?php

use App\Models\User;
use App\Support\CommunityFeed;
use Illuminate\Support\Facades\File;

test('the community page renders the maison community component', function () {
    $this->get('/nl/community')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/community')
            ->missing('posts')
        );
});

test('guests do not receive the community feed props', function () {
    $this->get(localized('maison.community'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.user', null)
            ->missing('posts')
        );
});

test('authenticated members receive a scrollable community feed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(localized('maison.community'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/community')
            ->has('posts.data', CommunityFeed::PER_PAGE)
            ->where('posts.current_page', 1)
            ->where('posts.last_page', (int) ceil(count(CommunityFeed::posts()) / CommunityFeed::PER_PAGE))
            ->has('posts.data.0.comments')
        );
});

test('authenticated members can load the next community feed page', function () {
    $user = User::factory()->create();
    $secondPageFirstId = CommunityFeed::posts()[CommunityFeed::PER_PAGE]['id'];

    $this->actingAs($user)
        ->get(route('maison.community', [
            'locale' => defaultLocale(),
            'page' => 2,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('posts.current_page', 2)
            ->where('posts.data.0.id', $secondPageFirstId)
        );
});

test('the community login gate opens the real auth modal', function () {
    $source = File::get(resource_path('js/components/maison/community/community-login-gate.tsx'));

    expect($source)
        ->toContain('openAuth')
        ->toContain("'login'")
        ->not->toContain('type="password"')
        ->not->toContain('onLogin');
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

test('community tabs hide overflow scrollbars', function () {
    $source = File::get(resource_path('js/components/maison/community/community-tabs.tsx'));

    expect($source)
        ->toContain('overflow-y-hidden')
        ->toContain('[&::-webkit-scrollbar]:hidden');
});

test('the community feed uses a circle sheet instead of an inline sidebar column', function () {
    $feed = File::get(resource_path('js/components/maison/community/community-feed.tsx'));
    $panel = File::get(resource_path('js/components/maison/community/community-circle-panel.tsx'));

    expect($feed)
        ->toContain('CommunityCirclePanel')
        ->not->toContain('lg:grid-cols-[1fr_360px]');

    expect($panel)
        ->toContain('Sheet')
        ->toContain('Uw Circle')
        ->toContain('FeedSidebar');
});

test('the community feed uses inertia infinite scroll', function () {
    $source = File::get(resource_path('js/components/maison/community/community-feed.tsx'));

    expect($source)
        ->toContain('InfiniteScroll')
        ->toContain('data="posts"');
});
