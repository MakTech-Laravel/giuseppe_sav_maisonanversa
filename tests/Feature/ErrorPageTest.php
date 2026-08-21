<?php

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

test('the 404 recovery links sit on the chocolate hero', function () {
    expect(File::get(resource_path('js/pages/errors/404.tsx')))
        ->toContain('variant="hero"')
        ->toContain('variant="ghost"')
        ->not->toContain('variant="choc"');
});
