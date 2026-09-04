<?php

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\CommunityEvent;
use App\Models\CommunitySession;
use App\Models\EventRsvp;
use App\Models\Order;
use App\Models\User;
use App\Services\Edition\EditionAllocator;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
});

test('any customer can view the member passport page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(localized('member.lidpaspoort'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('member/lidpaspoort')
            ->has('passport.membership')
            ->has('passport.badges')
            ->has('passport.sessions')
            ->has('passport.events')
        );
});

test('registered members without the circle still cannot open the heritage passport', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(localized('member.passport'))
        ->assertForbidden();
});

test('the member passport derives badges from activity', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

    $order = Order::factory()->forUser($user)->create(['status' => OrderStatus::Incomplete]);
    app(EditionAllocator::class)->allocate($order);
    $order->update(['status' => OrderStatus::Paid]);

    $partnerClub = Club::factory()->create(['is_partner' => true]);
    $session = CommunitySession::factory()->create([
        'host_id' => $user->id,
        'club_id' => $partnerClub->id,
        'sport' => SessionSport::Padel,
    ]);
    $session->participants()->create(['user_id' => $user->id]);

    $event = CommunityEvent::factory()->opening()->create();
    EventRsvp::factory()->create([
        'community_event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(localized('member.lidpaspoort'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('passport.membership.status', 'founding_circle')
            ->where('passport.membership.edition_number', '002')
            ->where('passport.sessions.hosted_count', 1)
            ->where('passport.events.past_count', 0)
        );
});

test('customers cannot create exclusive events through the admin route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.events.store', ['locale' => 'nl']), [
            'title' => 'Member Event',
            'description' => 'Should not work.',
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'location' => 'Antwerp',
            'capacity' => 20,
        ])
        ->assertForbidden();

    expect(CommunityEvent::query()->where('title', 'Member Event')->exists())->toBeFalse();
});

test('the member events page has no create call to action', function () {
    $source = file_get_contents(resource_path('js/pages/maison/events/index.tsx'));

    expect($source)
        ->not->toContain('Evenement aanmaken')
        ->not->toContain('events.create')
        ->toContain('Mijn aanmeldingen');
});
