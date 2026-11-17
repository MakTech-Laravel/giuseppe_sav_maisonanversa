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
                && str_contains($body, 'Founding Circle Register')
                && ! str_contains(strtolower($body), '<script')));
});

test('client-approved english and french legal bodies are served exactly', function () {
    $this->seed(LegalPageSeeder::class);

    $this->get(route('maison.privacy', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'Cookie settings')
                && str_contains($body, 'maison_consent')
                && str_contains($body, 'Founding Circle Register')
                && ! str_contains($body, 'Privacybeleid')));

    $this->get(route('maison.privacy', ['locale' => 'fr']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'Paramètres des cookies')
                && str_contains($body, 'maison_consent')
                && ! str_contains($body, 'Privacybeleid')));

    $this->get(route('maison.terms', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'Heritage Nº 001')
                && str_contains($body, '/en/withdrawal')));

    $this->get(route('maison.withdrawal', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, '______________________')
                && str_contains($body, 'hello@maisonanversa.com')));
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

test('translated legal html can still fall back to dutch when a locale row is missing', function () {
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();
    $page->translations()->where('locale', 'fr')->where('column', 'body')->delete();

    $this->get(route('maison.privacy', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'Cookie settings')));

    $this->get(route('maison.privacy', ['locale' => 'fr']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'Founding Circle Register')
                && str_contains($body, 'Cookie-instellingen')));
});

test('legacy markdown legal bodies are converted to html for visitors', function () {
    $page = LegalPage::query()->where('slug', 'privacy')->firstOrFail();
    $page->update(['body' => "## Privacybeleid\n\nFirst paragraph."]);

    $this->get(localized('maison.privacy'))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, '<h2>Privacybeleid</h2>')
                && str_contains($body, '<p>First paragraph.</p>')));
});

test('the legal layout shows the november 2026 update date', function () {
    $layout = file_get_contents(resource_path('js/components/maison/legal/legal-page-layout.tsx'));

    expect($layout)
        ->toContain("t('Last updated: November 2026')")
        ->not->toContain("t('Last updated: July 2026')");
});

test('the shipping page matches the terms withdrawal flow in every locale', function () {
    $this->seed(LegalPageSeeder::class);

    $this->get(route('maison.shipping', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'Europese Unie')
                && str_contains($body, '/nl/withdrawal')
                && str_contains($body, 'Bestellingen → Retourneren')
                && ! str_contains($body, 'Buiten Europa')
                && ! str_contains($body, 'ongebruikt zijn: niet bespeeld')));

    $this->get(route('maison.shipping', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'European Union')
                && str_contains($body, '/en/withdrawal')
                && str_contains($body, 'Orders → Return')
                && ! str_contains($body, 'Outside Europe')));

    $this->get(route('maison.shipping', ['locale' => 'fr']))
        ->assertOk()
        ->assertInertia(fn ($inertia) => $inertia
            ->where('legalPage.body', fn (string $body): bool => str_contains($body, 'Union européenne')
                && str_contains($body, '/fr/withdrawal')
                && str_contains($body, 'Commandes → Retourner')));
});

test('the footer and cookie banner support reopening cookie settings', function () {
    $footer = file_get_contents(resource_path('js/components/maison/shell/site-footer.tsx'));
    $banner = file_get_contents(resource_path('js/components/maison/cookie-consent-banner.tsx'));

    expect($footer)
        ->toContain('openCookieSettings')
        ->toContain("t('Cookie settings')")
        ->and($banner)
        ->toContain('COOKIE_SETTINGS_EVENT')
        ->toContain('forceOpen')
        ->toContain('Analytics')
        ->not->toContain('Marketing');
});
