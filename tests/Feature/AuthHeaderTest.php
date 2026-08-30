<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

test('the auth menu is wired into the public shell', function () {
    expect(File::get(resource_path('js/components/maison/shell/site-topbar.tsx')))
        ->toContain('AuthMenu')
        ->toContain('Heritage Letter')
        ->toContain('onNewsletter')
        ->not->toContain('isAuthenticated')
        ->not->toContain('MaisonLink');

    $nav = File::get(resource_path('js/components/maison/shell/site-nav.tsx'));

    expect($nav)
        ->toContain('AuthMenu')
        ->toContain('ma-lg:hidden')
        ->toContain("aria-label={t('Menu')}");
});

test('mobile site nav shows auth beside the menu toggle', function () {
    $nav = File::get(resource_path('js/components/maison/shell/site-nav.tsx'));

    expect($nav)
        ->toContain('flex shrink-0 items-center gap-1 ma-lg:hidden')
        ->toContain('<AuthMenu compact />')
        ->not->toContain('<AuthMenu compact className="ma-lg:hidden" />');
});

test('guests visiting home receive auth flash props when redirected from a protected route', function () {
    $this->get(localized('member.dashboard'))
        ->assertRedirect(localized('maison.home', absolute: false))
        ->assertSessionHas('open_auth_modal', 'login');
});

test('authenticated members see their name in shared auth props on the home page', function () {
    $user = User::factory()->create(['name' => 'Heritage Member']);

    $this->actingAs($user)
        ->get(localized('maison.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.name', 'Heritage Member')
            ->where('auth.user.is_admin', false)
            ->has('auth.user.dashboard_url')
        );
});
