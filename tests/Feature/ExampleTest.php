<?php

test('the site root redirects to the default locale without a preference', function () {
    $this->get(route('home'))
        ->assertRedirect('/'.config('maison.default_locale'));
});

test('each supported locale is reachable', function (string $locale) {
    $this->get("/{$locale}")->assertOk();
})->with(['nl', 'en', 'fr']);

test('an unsupported locale is not found', function () {
    $this->get('/de')->assertNotFound();
});

test('the resolved locale is shared with the front end', function () {
    $this->get('/fr')->assertInertia(
        fn ($page) => $page
            ->where('locale', 'fr')
            ->where('availableLocales', ['nl', 'en', 'fr'])
    );
});
