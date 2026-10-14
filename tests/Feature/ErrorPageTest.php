<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

test('unknown public paths render the branded inertia 404', function () {
    $this->get('/nl/bestaat-niet')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('errors/404')
            ->where('seo.robots', 'noindex, nofollow')
        );
});

test('unknown journal slugs render the branded inertia 404', function () {
    $this->get('/nl/journal/niet-bestaand')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('errors/404'));
});

test('unprefixed unknown paths still share the public shell props', function () {
    $this->get('/niet-gevonden')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('errors/404')
            ->has('foundingRegister.inscribed_count')
            ->has('foundingRegister.places_total')
            ->has('site')
            ->has('locale')
        );
});

test('a missing route model renders the 404 with the public shell props', function () {
    $this->actingAs(User::factory()->create())
        ->get('/en/community/sessions/999999')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('errors/404')
            ->has('foundingRegister.inscribed_count')
            ->has('foundingRegister.places_total')
            ->has('site')
            ->has('auth.user')
            ->where('locale', 'en')
            ->where('seo.robots', 'noindex, nofollow')
        );
});

test('the 404 recovery links sit on the chocolate hero', function () {
    expect(File::get(resource_path('js/pages/errors/404.tsx')))
        ->toContain('variant="hero"')
        ->toContain('variant="ghost"')
        ->not->toContain('variant="choc"');
});
