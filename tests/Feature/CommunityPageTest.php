<?php

use App\Models\CommunityPost;
use App\Models\User;
use App\Support\CommunityFeed;
use App\Support\CommunityPostPresenter;
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
    CommunityPost::factory()->count(CommunityFeed::PER_PAGE + 4)->create();

    $response = $this->actingAs($user)
        ->get(localized('maison.community'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('maison/community')
            ->has('posts.data', CommunityFeed::PER_PAGE)
            ->where('posts.current_page', 1)
            ->where('posts.last_page', 2)
            ->has('posts.data.0.comments')
            ->has('posts.data.0.excerpt')
            ->has('posts.data.0.is_truncated')
        );

    $inertiaPage = $response->viewData('page');
    $inertiaPage = is_array($inertiaPage) ? $inertiaPage : json_decode(json_encode($inertiaPage), true);

    expect($inertiaPage['scrollProps']['posts'] ?? null)
        ->toMatchArray([
            'pageName' => 'page',
            'currentPage' => 1,
            'nextPage' => 2,
            'previousPage' => null,
            'reset' => false,
        ]);
});

test('authenticated members can load the next community feed page', function () {
    $user = User::factory()->create();
    CommunityPost::factory()->count(CommunityFeed::PER_PAGE + 4)->create();
    $secondPageFirst = CommunityPost::query()->latest()->skip(CommunityFeed::PER_PAGE)->first();

    $this->actingAs($user)
        ->get(route('maison.community', [
            'locale' => defaultLocale(),
            'page' => 2,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('posts.current_page', 2)
            ->where('posts.data.0.id', (string) $secondPageFirst->id)
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
        ->not->toContain('innerHTML')
        ->not->toContain('Melden')
        ->not->toContain('Delen');
});

test('community tabs are url based links', function () {
    $tabsSource = File::get(resource_path('js/components/maison/community/community-tabs.tsx'));
    $layoutSource = File::get(resource_path('js/components/maison/community/community-layout.tsx'));

    expect($tabsSource)
        ->toContain("query: { tab: 'courts' }")
        ->toContain('sessionRoutes.index')
        ->toContain('eventRoutes.index')
        ->toContain('aria-selected')
        ->not->toContain('onTabChange')
        ->not->toContain('switchCommTab');

    expect($layoutSource)
        ->toContain('tab === \'courts\'')
        ->not->toContain('useState');
});

test('a community toast helper exists for member feedback', function () {
    $source = file_get_contents(resource_path('js/components/maison/community/community-toast.tsx'));

    expect($source)
        ->toContain('useCommunityToast')
        ->toContain('CommunityToast');
});

test('community tabs mark the active tab clearly', function () {
    $source = File::get(resource_path('js/components/maison/community/community-tabs.tsx'));

    expect($source)
        ->toContain('aria-selected')
        ->toContain('after:bg-choc')
        ->toContain('role="tablist"');
});

test('community courts use a stacked layout without a side column', function () {
    $source = File::get(resource_path('js/components/maison/community/community-courts.tsx'));

    expect($source)
        ->toContain('md:grid-cols-2')
        ->not->toContain('lg:grid-cols-[1fr_420px]');
});

test('sessions live on their own page with three tabs', function () {
    $index = File::get(resource_path('js/pages/maison/sessions/index.tsx'));
    $tabs = File::get(resource_path('js/components/maison/community/sessions/session-tabs.tsx'));

    expect($index)
        ->toContain('SessionCard')
        ->toContain('SessionTabs')
        ->and($tabs)
        ->toContain('role="tablist"');

    expect(File::exists(resource_path('js/components/maison/community/community-sessions.tsx')))->toBeFalse();
});

test('create session uses community tabs, schedule pickers and a live preview', function () {
    $create = File::get(resource_path('js/pages/maison/sessions/create.tsx'));

    expect($create)
        ->toContain('CommunityTabs')
        ->toContain('SessionScheduleFields')
        ->toContain('SessionDraftPreview')
        ->toContain('lg:grid-cols-[minmax(0,1fr)_20rem]')
        ->not->toContain('SessionBreadcrumb')
        ->not->toContain('DeepL vult NL, EN en FR');
});

test('session schedule pickers show every minute and keep selected calendar days readable', function () {
    $schedule = File::get(resource_path('js/components/maison/community/sessions/session-schedule-fields.tsx'));
    $calendar = File::get(resource_path('js/components/ui/calendar.tsx'));
    $steps = File::get(resource_path('js/components/maison/community/sessions/session-step.tsx'));

    expect($schedule)
        ->toContain('length: 60')
        ->not->toContain("['00', '15', '30', '45']");

    expect($calendar)
        ->toContain('[&_button]:bg-choc')
        ->toContain('[&_button]:text-cream');

    expect($steps)->toContain('whitespace-normal');
});

test('the community feed does not render the circle featured panel', function () {
    $feed = File::get(resource_path('js/components/maison/community/community-feed.tsx'));

    expect($feed)
        ->not->toContain('CommunityCirclePanel')
        ->not->toContain('lg:grid-cols-[1fr_360px]');
});

test('the community feed includes excerpt metadata for long posts', function () {
    $user = User::factory()->create();
    $longContent = str_repeat('Long community post. ', 20);

    CommunityPost::factory()->create(['content' => $longContent]);
    CommunityPost::factory()->create(['content' => 'Short post']);

    $this->actingAs($user)
        ->get(localized('maison.community'))
        ->assertOk()
        ->assertInertia(function ($page) use ($longContent) {
            $page->component('maison/community');

            $posts = collect($page->toArray()['props']['posts']['data'] ?? []);
            $long = $posts->first(
                fn (array $post): bool => str_contains($post['content'], 'Long community post'),
            );
            $short = $posts->firstWhere('content', 'Short post');

            expect($long)->not->toBeNull()
                ->and($long['is_truncated'] ?? null)->toBeTrue()
                ->and($long['excerpt'] ?? null)->toBe(CommunityPostPresenter::excerpt($longContent))
                ->and($short['is_truncated'] ?? null)->toBeFalse();
        });
});

test('the community feed uses inertia infinite scroll', function () {
    $source = File::get(resource_path('js/components/maison/community/community-feed.tsx'));

    expect($source)
        ->toContain('InfiniteScroll')
        ->toContain('data="posts"')
        ->toContain('scrollProps?.posts')
        ->toContain("router.on('beforeUpdate'")
        ->toContain('flushSync');
});
