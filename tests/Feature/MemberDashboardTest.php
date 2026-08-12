<?php

use App\Models\User;

test('guests are redirected from the member dashboard', function () {
    $this->get(localized('member.dashboard'))
        ->assertRedirect(localized('maison.home', absolute: false));
});

test('members can view the dashboard shell', function () {
    $user = User::factory()->create(['name' => 'Circle Member']);

    $this->actingAs($user)
        ->get(localized('member.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('member/dashboard')
            ->where('member.name', 'Circle Member')
            ->where('member.editionNumber', '047')
            ->has('stats', 3)
        );
});

test('members can view heritage under a locale prefix', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('member.heritage', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('member/heritage'));
});

test('members can view heritage, orders, passport, circle and letter shells', function (
    string $route,
    string $component,
) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(localized($route))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['member.heritage', 'member/heritage'],
    ['member.orders', 'member/orders'],
    ['member.passport', 'member/passport'],
    ['member.circle', 'member/circle'],
    ['member.letter', 'member/letter'],
    ['member.profile', 'member/profile'],
    ['member.security', 'member/security'],
]);

test('members can update their profile and username from the member area', function () {
    $user = User::factory()->create(['username' => 'old_handle']);

    $this->actingAs($user)
        ->patch(localized('member.profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'username' => 'new_handle',
        ])
        ->assertRedirect(localized('member.profile', absolute: false));

    expect($user->fresh()->username)->toBe('new_handle')
        ->and($user->fresh()->name)->toBe('Updated Name');
});

test('the member layout is wired for member pages', function () {
    $source = file_get_contents(resource_path('js/app.tsx'));

    expect($source)
        ->toContain("name.startsWith('member/')")
        ->toContain('MemberLayout');
});

test('the member nav includes the client feedback sections', function () {
    $source = file_get_contents(resource_path('js/components/member/member-nav.tsx'));

    foreach ([
        'Dashboard',
        'My Heritage',
        'Orders',
        'Passport',
        'Founding Circle',
        'Community',
        'Heritage Letter',
        'Profile & Account',
        'Logout',
    ] as $label) {
        expect($source)->toContain($label);
    }
});
