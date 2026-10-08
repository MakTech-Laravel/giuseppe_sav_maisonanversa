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

test('mobile site nav keeps auth at the top of the opened drawer', function () {
    $nav = File::get(resource_path('js/components/maison/shell/site-nav.tsx'));

    expect($nav)
        ->toContain('id="maison-nav-links"')
        ->toContain('<AuthMenu />')
        ->toContain('ma-lg:hidden')
        ->not->toContain('menuPlacement="up"')
        ->not->toContain('<AuthMenu compact />')
        ->toContain("aria-label={t('Menu')}")
        ->toContain('className="flex size-11 shrink-0 flex-col items-center justify-center gap-1.25 ma-lg:hidden"');

    $drawer = strstr($nav, 'id="maison-nav-links"');
    $links = strstr($drawer, '<ul');
    $beforeLinks = strstr($drawer, '<ul', true);

    expect($beforeLinks)->toContain('<AuthMenu />')
        ->and($beforeLinks)->toContain('ma-lg:hidden')
        ->and($links)->not->toContain('AuthMenu');
});

test('auth menu keeps login for guests and the profile initial after login', function () {
    $authMenu = File::get(resource_path('js/components/maison/shell/auth-menu.tsx'));

    expect($authMenu)
        ->toContain("openAuth('login')")
        ->toContain("t('Inloggen')")
        ->toContain('rounded-full border border-gold/35')
        ->toContain('firstName.charAt(0).toUpperCase()')
        ->toContain("t('Uitloggen')")
        ->toContain("menuPlacement?: 'down' | 'up'")
        ->toContain('bottom-[calc(100%+0.5rem)]');
});

test('frontend layout closes the auth modal once the visitor is authenticated', function () {
    $layout = File::get(resource_path('js/layouts/frontend-layout.tsx'));

    expect($layout)
        ->toContain('isAuthenticated')
        ->toContain("userModal !== 'auth'")
        ->toContain('setUserModal(null)')
        ->toContain('!isAuthenticated &&');
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

test('authenticated members keep shared auth props after an inertia login redirect target', function () {
    $user = User::factory()->create(['name' => 'Menu Member']);

    $this->actingAs($user)
        ->get(localized('maison.story'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.name', 'Menu Member')
            ->has('auth.user.dashboard_url')
        );
});
