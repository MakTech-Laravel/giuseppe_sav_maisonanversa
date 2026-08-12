<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

test('the auth menu is wired into the public shell', function () {
    expect(File::get(resource_path('js/components/maison/shell/site-topbar.tsx')))
        ->toContain('AuthMenu')
        ->toContain('isAuthenticated')
        ->not->toContain('MaisonLink');

    expect(File::get(resource_path('js/components/maison/shell/site-nav.tsx')))
        ->toContain('AuthMenu');
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
