<?php

use App\Models\LegalPage;
use Database\Seeders\LegalPageSeeder;

test('legal pages are served from cms records as sanitized html', function () {
    $this->seed(LegalPageSeeder::class);

    $this->get(localized('maison.privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('legalPage.slug', 'privacy')
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, '<h2>')
                && ! str_contains(strtolower($body), '<script')));
});

test('unpublished legal pages fall back to sanitized html', function () {
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();
    $page->update(['is_published' => false]);

    $this->get(localized('maison.privacy'))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, '<p>')
                && str_contains($body, 'Privacybeleid')));
});

test('translated legal html is used for other locales and falls back to dutch', function () {
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();
    $page->translations()->updateOrCreate(
        ['locale' => 'en', 'column' => 'body'],
        [
            'value' => '<h2>Privacy policy</h2><p>English copy.</p>',
            'source_hash' => $page->translationSourceHash('body'),
        ],
    );

    $this->get(route('maison.privacy', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'Privacy policy')));

    $this->get(route('maison.privacy', ['locale' => 'fr']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'Privacybeleid')));
});

test('legacy markdown legal bodies are converted to html for visitors', function () {
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();
    $page->update(['body' => "## Privacybeleid\n\nEerste alinea."]);

    $this->get(localized('maison.privacy'))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, '<h2>Privacybeleid</h2>')
                && str_contains($body, '<p>Eerste alinea.</p>')));
});
