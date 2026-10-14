<?php

use App\Enums\NotificationType;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Events\UserNotificationCreated;
use App\Jobs\FanOutNotificationJob;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['maison.admin_type_grants_all_permissions' => false]);
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->staff = User::factory()->admin()->create();
    $this->staff->givePermissionTo(PermissionEnum::NOTIFICATIONS_TEST->value);
});

function testNotificationPayload(array $overrides = []): array
{
    return [
        'target' => 'self',
        'type' => NotificationType::Info->value,
        'title' => 'Testmelding',
        'message' => 'Realtime test.',
        'mode' => 'sync',
        ...$overrides,
    ];
}

test('guests and customers cannot open the notification test page', function () {
    $this->get(localized('admin.notifications.test.index'))->assertRedirect();

    $this->actingAs(User::factory()->customer()->create())
        ->get(localized('admin.notifications.test.index'))
        ->assertForbidden();
});

test('staff without notifications.test are forbidden', function () {
    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo(PermissionEnum::DASHBOARD_VIEW->value);

    $this->actingAs($staff);

    $this->get(localized('admin.notifications.test.index'))->assertForbidden();
    $this->postJson(localized('admin.notifications.test.send'), testNotificationPayload())->assertForbidden();
});

test('the admin role is granted notifications.test', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::ADMIN->value);

    expect($admin->can(PermissionEnum::NOTIFICATIONS_TEST->value))->toBeTrue();
});

test('staff with the permission see the test page', function () {
    $this->actingAs($this->staff)
        ->get(localized('admin.notifications.test.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/notifications/test')
            ->has('types', count(NotificationType::cases()))
            ->has('queueConnection'));
});

test('the send endpoint validates its input', function () {
    $this->actingAs($this->staff)
        ->postJson(localized('admin.notifications.test.send'), [
            'target' => 'user',
            'email' => 'missing@example.com',
            'type' => 'nope',
            'title' => '',
            'message' => '',
            'link_url' => 'not-a-url',
            'mode' => 'later',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'type', 'title', 'message', 'link_url', 'mode']);
});

test('sync mode stores a self notification and broadcasts it directly', function () {
    Event::fake([UserNotificationCreated::class]);

    $this->actingAs($this->staff)
        ->postJson(localized('admin.notifications.test.send'), testNotificationPayload([
            'link_url' => 'https://maisonanversa.test/page',
            'link_label' => 'Open',
        ]))
        ->assertOk()
        ->assertJsonPath('data.mode', 'sync')
        ->assertJsonPath('data.recipients', 1)
        ->assertJsonPath('data.event', 'notification.created')
        ->assertJsonPath('data.channels.0', "private-App.Models.User.{$this->staff->id}");

    $row = $this->staff->userNotifications()->with('notification')->sole();

    expect($row->notification->title)->toBe('Testmelding')
        ->and($row->notification->data['link_url'])->toBe('https://maisonanversa.test/page')
        ->and($row->notification->created_by)->toBe($this->staff->id);

    Event::assertDispatched(UserNotificationCreated::class, fn (UserNotificationCreated $event): bool => $event->userNotification->is($row));
});

test('queue mode pushes the fan-out job', function () {
    Queue::fake();

    $this->actingAs($this->staff)
        ->postJson(localized('admin.notifications.test.send'), testNotificationPayload(['mode' => 'queue']))
        ->assertOk()
        ->assertJsonPath('data.mode', 'queue');

    Queue::assertPushed(FanOutNotificationJob::class);
});

test('the admins target reaches every admin but no customer', function () {
    Queue::fake();

    $otherAdmin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();

    $this->actingAs($this->staff)
        ->postJson(localized('admin.notifications.test.send'), testNotificationPayload([
            'target' => 'admins',
            'mode' => 'queue',
        ]))
        ->assertOk();

    expect($otherAdmin->userNotifications()->count())->toBe(1)
        ->and($this->staff->userNotifications()->count())->toBe(1)
        ->and($customer->userNotifications()->count())->toBe(0);
});

test('the user target reaches the member by email', function () {
    Queue::fake();

    $member = User::factory()->customer()->create(['email' => 'lid@example.com']);

    $this->actingAs($this->staff)
        ->postJson(localized('admin.notifications.test.send'), testNotificationPayload([
            'target' => 'user',
            'email' => 'lid@example.com',
            'mode' => 'queue',
        ]))
        ->assertOk()
        ->assertJsonPath('data.recipients', 1);

    expect($member->userNotifications()->count())->toBe(1)
        ->and(UserNotification::query()->count())->toBe(1);
});
