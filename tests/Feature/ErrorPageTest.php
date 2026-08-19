<?php

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
