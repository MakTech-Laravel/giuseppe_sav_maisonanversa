<?php

use App\Enums\RoleEnum;
use App\Enums\SubscriberSource;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('heritage letter submissions are rate limited', function () {
    Queue::fake();
    $payload = [
        'email' => 'limit@example.com',
        'source' => SubscriberSource::Home->value,
    ];

    foreach (range(1, 5) as $attempt) {
        $this->from('/nl')->post(route('maison.heritage-letter.store', ['locale' => 'nl']), [
            ...$payload,
            'email' => "limit{$attempt}@example.com",
            'source' => SubscriberSource::Home->value,
        ])->assertRedirect();
    }

    $this->post(route('maison.heritage-letter.store', ['locale' => 'nl']), $payload)
        ->assertTooManyRequests();
});

test('viewers cannot manage orders', function () {
    // The temporary AdminTypePermissionBypass grants every staff account full
    // access while granular permission UI is hidden; disable it here so this
    // test exercises real permission enforcement for the viewer role.
    config(['maison.admin_type_grants_all_permissions' => false]);

    $viewer = User::factory()->admin()->create();
    $viewer->syncRoles(RoleEnum::VIEWER->value);
    $viewer->syncTypeFromRoles();

    $this->actingAs($viewer)
        ->get(route('admin.orders.index'))
        ->assertForbidden();
});

test('stripe webhook csrf exception is registered and secrets stay server-side', function () {
    $bootstrap = File::get(base_path('bootstrap/app.php'));

    expect($bootstrap)->toContain("'stripe/*'");

    $frontend = File::get(resource_path('js/app.tsx'));

    expect($frontend)
        ->not->toContain('BREVO_API_KEY')
        ->not->toContain('STRIPE_SECRET')
        ->not->toContain('STRIPE_WEBHOOK_SECRET');
});
