<?php

use App\Enums\RoleEnum;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('site settings are shared globally', function () {
    // SiteSetting is a singleton row: the global test suite seeder already
    // creates it, so this test updates the existing record rather than
    // inserting a second one that SiteSetting::current() would never read.
    SiteSetting::current()->update([
        'phone' => '+32123456789',
        'whatsapp' => '32123456789',
        'email_hello' => 'hello@test.com',
        'email_press' => 'press@test.com',
        'instagram_url' => 'https://www.instagram.com/maison',
        'boutique_lat' => '51.2200000',
        'boutique_lng' => '4.4040000',
    ]);

    $this->get('/nl/contact')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('site.emailHello', 'hello@test.com')
            ->where('site.phone', '+32123456789')
            ->where('site.whatsappHref', 'https://wa.me/32123456789'));
});

test('admin can update site settings', function () {
    SiteSetting::current();

    $this->actingAs($this->admin)
        ->patch(route('admin.site-settings.update', ['locale' => 'nl']), [
            'phone' => '+32999888777',
            'whatsapp' => '32999888777',
            'email_hello' => 'contact@maison.test',
            'email_press' => 'press@maison.test',
            'instagram_url' => 'https://www.instagram.com/maisonanversa',
            'boutique_lat' => '51.221',
            'boutique_lng' => '4.405',
            'announcement_text' => 'Heritage No.001 — limited edition',
        ])
        ->assertRedirect();

    expect(SiteSetting::current())
        ->phone->toBe('+32999888777')
        ->email_hello->toBe('contact@maison.test')
        ->announcement_text->toBe('Heritage No.001 — limited edition');
});

test('staff can open site settings with announcement translations', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.site-settings.edit', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/site-settings/edit')
            ->has('settings.phone')
            ->has('translations.nl.announcement_text')
            ->has('translations.en.announcement_text')
            ->has('translationStatus.fr.announcement_text'));
});

test('announcement text is shared per locale after deepl', function () {
    fakeDeepLTranslations();

    SiteSetting::current()->update([
        'announcement_text' => 'Heritage No.001 — beperkt',
    ]);

    $this->get('/nl/contact')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('site.announcementText', 'Heritage No.001 — beperkt'));

    $this->get('/en/contact')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('site.announcementText', 'EN Heritage No.001 — beperkt'));
});

test('staff can manually update announcement translations', function () {
    $settings = SiteSetting::current();
    $settings->update([
        'announcement_text' => 'Nederlandse aankondiging',
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.site-settings.translations.update', ['locale' => 'nl']), [
            'nl' => ['announcement_text' => 'Aangepaste aankondiging'],
            'en' => ['announcement_text' => 'Custom announcement'],
            'fr' => ['announcement_text' => 'Annonce personnalisée'],
        ])
        ->assertRedirect(route('admin.site-settings.edit', ['locale' => 'nl']));

    $settings->refresh();

    expect($settings->announcement_text)->toBe('Aangepaste aankondiging')
        ->and($settings->translated('announcement_text', 'en'))->toBe('Custom announcement')
        ->and($settings->translated('announcement_text', 'fr'))->toBe('Annonce personnalisée');

    $this->get('/en/contact')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('site.announcementText', 'Custom announcement'));
});

test('staff can retranslate announcement for one locale', function () {
    fakeDeepLTranslations();

    $settings = SiteSetting::current();
    $settings->update([
        'announcement_text' => 'Bron aankondiging',
    ]);
    $settings->translations()->updateOrCreate(
        ['locale' => 'fr', 'column' => 'announcement_text'],
        ['value' => 'Keep FR announcement', 'source_hash' => $settings->translationSourceHash('announcement_text')],
    );

    $this->actingAs($this->admin)
        ->post(route('admin.site-settings.translate', ['locale' => 'nl']), [
            'target_locale' => 'en',
        ])
        ->assertRedirect(route('admin.site-settings.edit', ['locale' => 'nl']));

    $settings->refresh();

    expect($settings->translated('announcement_text', 'en'))->toBe('EN Bron aankondiging')
        ->and($settings->translated('announcement_text', 'fr'))->toBe('Keep FR announcement');
});

test('empty announcement is shared as null', function () {
    SiteSetting::current()->update([
        'announcement_text' => null,
    ]);

    $this->get('/nl/contact')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('site.announcementText', null));
});
