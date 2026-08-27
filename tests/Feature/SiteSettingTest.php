<?php

use App\Enums\RoleEnum;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

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
