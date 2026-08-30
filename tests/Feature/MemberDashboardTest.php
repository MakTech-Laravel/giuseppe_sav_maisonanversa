<?php

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Enums\UserGender;
use App\Models\Order;
use App\Models\User;
use App\Services\Edition\EditionAllocator;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('guests are redirected from the member dashboard', function () {
    $this->get(localized('member.dashboard'))
        ->assertRedirect(localized('maison.home', absolute: false));
});

test('members can view the dashboard shell', function () {
    $user = User::factory()->create(['name' => 'Circle Member']);

    $this->actingAs($user)
        ->get(localized('member.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('member/dashboard')
            ->where('member.name', 'Circle Member')
            ->where('member.editionNumber', '—')
            ->has('stats', 3)
            ->missing('notifications')
        );
});

test('members can view heritage under a locale prefix', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);
    $order = Order::factory()->forUser($user)->create();
    app(EditionAllocator::class)->allocate($order);
    $order->update(['status' => OrderStatus::Paid]);

    $this->actingAs($user)
        ->get(route('member.heritage', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('member/heritage'));
});

test('members can view heritage, orders, passport, circle and letter shells', function (
    string $route,
    string $component,
) {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $order = Order::factory()->forUser($user)->create();
    app(EditionAllocator::class)->allocate($order);
    $order->update(['status' => OrderStatus::Paid]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(localized($route))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    ['member.heritage', 'member/heritage'],
    ['member.orders', 'member/orders'],
    ['member.passport', 'member/passport'],
    ['member.circle', 'member/circle'],
    ['member.letter', 'member/letter'],
    ['member.email-preferences', 'member/email-preferences'],
    ['member.profile', 'member/profile'],
    ['member.security', 'member/security'],
]);

test('members can update their profile gender from the member area', function () {
    $user = User::factory()->create([
        'username' => 'old_handle',
        'gender' => UserGender::Male,
    ]);

    $this->actingAs($user)
        ->patch(localized('member.profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'gender' => UserGender::Female->value,
            'username' => 'new_handle',
        ])
        ->assertRedirect(localized('member.profile', absolute: false));

    expect($user->fresh()->username)->toBe('old_handle')
        ->and($user->fresh()->name)->toBe('Updated Name')
        ->and($user->fresh()->gender)->toBe(UserGender::Female);
});

test('members can upload and remove a profile avatar', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(localized('member.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->gender->value,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertRedirect(localized('member.profile', absolute: false));

    $user->refresh();

    expect($user->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);

    $this->actingAs($user)
        ->patch(localized('member.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->gender->value,
            'remove_avatar' => true,
        ])
        ->assertRedirect(localized('member.profile', absolute: false));

    expect($user->fresh()->avatar)->toBeNull();
});

test('members can view an order detail page', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->get(localized('member.orders.show', ['order' => $order->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('member/order-show')
            ->where('order.id', (string) $order->id)
            ->has('order.items')
            ->has('order.timeline')
        );
});

test('member orders list exposes the MA reference for each order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->get(localized('member.orders'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('member/orders')
            ->has('orders', 1)
            ->where('orders.0.id', (string) $order->id)
            ->where('orders.0.reference', $order->reference())
        );
});

test('unknown member orders return not found', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(localized('member.orders.show', ['order' => 'MISSING']))
        ->assertNotFound();
});

test('password confirmation for member security uses the member layout page', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(localized('member.security'))
        ->assertRedirect(route('password.confirm'));

    $this->get(route('password.confirm'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('member/confirm-password'));
});

test('the member layout is wired for member pages', function () {
    $source = file_get_contents(resource_path('js/lib/inertia-layouts.ts'));

    expect($source)
        ->toContain("name.startsWith('member/')")
        ->toContain('MemberLayout');
});

test('the member nav includes the client feedback sections', function () {
    $source = file_get_contents(resource_path('js/components/member/member-nav.tsx'));

    foreach ([
        "t('Dashboard')",
        "t('Bestellingen')",
        "t('Founding Circle')",
        "t('Gemeenschap')",
        "t('Heritage Letter')",
        "t('E-mailvoorkeuren')",
        "t('Profiel & account')",
        "t('Beveiliging')",
        "t('Uitloggen')",
    ] as $label) {
        expect($source)->toContain($label);
    }

    expect($source)
        ->not->toContain('My Heritage')
        ->not->toContain('Passport');
});

test('member dashboard demo payloads are translated for english', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('member.dashboard', ['locale' => 'en']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('member/dashboard')
            ->where('stats.0.label', 'Edition')
            ->where('stats.0.hint', 'Founding Edition')
            ->where('stats.1.value', 'None')
        );
});
