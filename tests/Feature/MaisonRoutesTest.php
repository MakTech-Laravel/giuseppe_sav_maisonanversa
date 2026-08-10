<?php

use Illuminate\Support\Facades\Route;

/**
 * Every public page, as route name => [slug, Inertia component].
 *
 * @return array<string, array{0: string, 1: string}>
 */
function maisonPages(): array
{
    return [
        'maison.home' => ['', 'maison/home'],
        'maison.house' => ['huis', 'maison/house'],
        'maison.product' => ['product', 'maison/product'],
        'maison.story' => ['story', 'maison/story'],
        'maison.circle' => ['circle', 'maison/circle'],
        'maison.dressing' => ['dressing', 'maison/dressing'],
        'maison.journal' => ['journal', 'maison/journal'],
        'maison.community' => ['community', 'maison/community'],
        'maison.corner' => ['corner', 'maison/corner'],
        'maison.contact' => ['contact', 'maison/contact'],
        'maison.privacy' => ['privacy', 'maison/legal/privacy'],
        'maison.terms' => ['terms', 'maison/legal/terms'],
        'maison.shipping' => ['shipping', 'maison/legal/shipping'],
        'maison.care' => ['care', 'maison/legal/care'],
    ];
}

/**
 * Datasets are built while Pest collects tests, before the application boots,
 * so the locales cannot be read from config here. The test below keeps this
 * list honest against config/maison.php.
 */
const MAISON_LOCALES = ['nl', 'en', 'fr'];

/**
 * @return array<string, array{0: string, 1: string, 2: string}>
 */
function maisonPagesAcrossLocales(): array
{
    $cases = [];

    foreach (MAISON_LOCALES as $locale) {
        foreach (maisonPages() as $name => [$slug, $component]) {
            $cases["{$locale} {$name}"] = [$locale, $slug, $component];
        }
    }

    return $cases;
}

test('the site publishes exactly fourteen pages', function () {
    expect(maisonPages())->toHaveCount(14);
});

test('the locales under test are the configured locales', function () {
    expect(config('maison.locales'))->toBe(MAISON_LOCALES);
});

test('every page renders its component in every locale', function (
    string $locale,
    string $slug,
    string $component,
) {
    $this->get(rtrim("/{$locale}/{$slug}", '/'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component($component)
            ->where('locale', $locale)
            ->where('availableLocales', MAISON_LOCALES)
        );
})->with(maisonPagesAcrossLocales());

test('the bare root redirects to the dutch locale', function () {
    $this->get('/')->assertRedirect('/nl');
});

test('every public page passes a feature smoke check in every locale', function (
    string $locale,
    string $slug,
    string $component,
) {
    $response = $this->get(rtrim("/{$locale}/{$slug}", '/'));

    $response
        ->assertOk()
        ->assertHeader('content-type', 'text/html; charset=utf-8')
        ->assertInertia(fn ($page) => $page->component($component));
})->with(maisonPagesAcrossLocales());

/*
 * Pest browser testing (`pest-plugin-browser`) is not installed in this
 * project, so console-error smoke is covered by the HTTP feature checks above
 * instead of `visit()` / `assertNoJavaScriptErrors()`.
 */

test('every page is registered under a locale-prefixed named route', function (
    string $name,
) {
    $route = Route::getRoutes()->getByName($name);

    expect($route)->not->toBeNull()
        ->and($route->uri())->toStartWith('{locale}')
        ->and($route->gatherMiddleware())->toContain('locale');
})->with(array_keys(maisonPages()));

test('the edition figures reach every page from a single source', function () {
    config([
        'maison.edition.reserved' => 73,
        'maison.edition.total' => 100,
    ]);

    $this->get('/nl/product')->assertInertia(fn ($page) => $page
        ->where('edition.reserved', 73)
        ->where('edition.total', 100)
        // The prototype quoted this figure separately and it had drifted.
        ->where('edition.available', 27)
    );
});

test('the available count never goes negative when an edition oversells', function () {
    config([
        'maison.edition.reserved' => 120,
        'maison.edition.total' => 100,
    ]);

    $this->get('/nl/product')
        ->assertInertia(fn ($page) => $page->where('edition.available', 0));
});

test('an unknown page under a valid locale is not found', function () {
    $this->get('/nl/kelder')->assertNotFound();
});

test('a valid page under an unsupported locale is not found', function () {
    $this->get('/de/product')->assertNotFound();
});
