<?php

use App\Enums\CommunityEventStatus;
use App\Enums\InquiryType;
use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Models\CommunityEvent;
use App\Models\CommunityReport;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('guests are redirected to the localized home page', function () {
    $this->get(localized('admin.dashboard'))
        ->assertRedirect(localized('maison.home', absolute: false));
});

test('authenticated staff can visit the admin dashboard', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($user)
        ->get(localized('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->has('stats', 6)
            ->where('stats.0.key', 'Klanten')
            ->where('stats.0.hintKey', 'Lid-accounts')
            ->where('stats.1.key', 'Beheerders')
            ->where('stats.2.key', 'Journal')
            ->where('stats.3.key', 'Openstaande bestellingen')
            ->where('stats.4.key', 'Ongeziene aanvragen')
            ->where('stats.5.key', 'Openstaande meldingen')
            ->has('recentCustomers')
            ->has('nextCommunityEvent')
            ->where('staffName', $user->name)
            ->where('locale', defaultLocale())
        );
});

test('the dashboard reports pending orders, unseen inquiries, and open reports', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    Order::factory()->create(['status' => OrderStatus::Paid]);
    Order::factory()->create(['status' => OrderStatus::Delivered]);

    Inquiry::factory()->create([
        'type' => InquiryType::Appointment,
        'seen_at' => null,
    ]);
    Inquiry::factory()->create([
        'type' => InquiryType::Feedback,
        'seen_at' => null,
    ]);
    Inquiry::factory()->create([
        'type' => InquiryType::Appointment,
        'seen_at' => now(),
    ]);

    CommunityReport::factory()->create(['status' => 'open']);
    CommunityReport::factory()->create(['status' => 'resolved']);

    $this->actingAs($user)
        ->get(localized('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.3.value', '1')
            ->where('stats.4.value', '2')
            ->where('stats.5.value', '1')
        );
});

test('the dashboard surfaces the next upcoming community event', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    CommunityEvent::factory()->create([
        'title' => 'Verleden Event',
        'status' => CommunityEventStatus::Closed,
        'starts_at' => now()->subDays(3),
    ]);

    $event = CommunityEvent::factory()->create([
        'title' => 'Aankomende Padel Avond',
        'status' => CommunityEventStatus::Opening,
        'starts_at' => now()->addDays(5),
    ]);

    $this->actingAs($user)
        ->get(localized('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('nextCommunityEvent.id', (string) $event->id)
            ->where('nextCommunityEvent.title', 'Aankomende Padel Avond')
        );
});

test('admin dashboard is reachable under each maison locale', function (string $locale) {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($user)
        ->get(route('admin.dashboard', ['locale' => $locale]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('locale', $locale)
            ->where('stats.0.key', 'Klanten')
        );
})->with(['nl', 'en', 'fr']);

test('users without dashboard permission are forbidden', function () {
    $user = User::factory()->customer()->create();

    $this->actingAs($user)
        ->get(localized('admin.dashboard'))
        ->assertForbidden();
});

test('legacy dashboard path redirects staff to the admin dashboard', function () {
    $user = User::factory()->admin()->create();
    $user->assignRole(RoleEnum::SUPER_ADMIN->value);
    $user->syncTypeFromRoles();

    $this->actingAs($user)
        ->get('/'.defaultLocale().'/dashboard')
        ->assertRedirect('/'.defaultLocale().'/admin/dashboard');
});
