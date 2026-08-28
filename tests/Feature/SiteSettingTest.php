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
            ->where('site.emailPress', 'press@test.com')
            ->where('site.emailPressHref', 'mailto:press@test.com')
            ->where('site.instagramUrl', 'https://www.instagram.com/maison')
            ->where('site.phone', '+32123456789')
            ->where('site.whatsappHref', 'https://wa.me/32123456789')
            ->missing('site.announcementText'));
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
        ])
        ->assertRedirect();

    expect(SiteSetting::current())
        ->phone->toBe('+32999888777')
        ->email_hello->toBe('contact@maison.test')
        ->instagram_url->toBe('https://www.instagram.com/maisonanversa');
});

test('staff can open the site settings form', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.site-settings.edit', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/site-settings/edit')
            ->has('settings.phone')
            ->has('settings.instagram_url')
            ->missing('settings.announcement_text')
            ->missing('translations')
            ->missing('translationStatus'));
});

test('organization structured data uses site settings contact channels', function () {
    SiteSetting::current()->update([
        'phone' => '+32 3 999 00 11',
        'email_hello' => 'hello@maisonanversa.test',
        'instagram_url' => 'https://www.instagram.com/maison-anversa-test/',
    ]);

    $this->get('/nl')->assertOk()->assertInertia(function ($page): void {
        $graphs = $page->toArray()['props']['seo']['jsonLd'][0]['@graph'];
        $organization = collect($graphs)->firstWhere('@type', 'Organization');

        expect($organization['telephone'])->toBe('+32 3 999 00 11')
            ->and($organization['email'])->toBe('hello@maisonanversa.test')
            ->and($organization['sameAs'])->toBe(['https://www.instagram.com/maison-anversa-test/']);
    });
});
