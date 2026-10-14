<?php

use App\Enums\NotificationType;
use App\Events\UserNotificationCreated;
use App\Jobs\FanOutNotificationJob;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

test('send creates one notification and de-duplicated recipient rows', function () {
    Queue::fake();

    $users = User::factory()->count(2)->create();

    $notification = app(NotificationService::class)->send(
        [$users[0], $users[0]->id, (string) $users[1]->id, 0],
        NotificationType::Info,
        'Welkom',
        'Een testbericht.',
        ['link_url' => 'https://example.com', 'link_label' => 'Open'],
    );

    expect($notification)->toBeInstanceOf(AppNotification::class)
        ->and($notification->type)->toBe(NotificationType::Info)
        ->and($notification->data)->toBe(['link_url' => 'https://example.com', 'link_label' => 'Open'])
        ->and(UserNotification::query()->count())->toBe(2);

    Queue::assertPushed(FanOutNotificationJob::class, fn (FanOutNotificationJob $job): bool => $job->notificationId === $notification->id);
});

test('send without recipients writes nothing', function () {
    Queue::fake();

    expect(app(NotificationService::class)->send([], NotificationType::Info, 'Leeg', 'Niemand'))->toBeNull()
        ->and(AppNotification::query()->count())->toBe(0);

    Queue::assertNothingPushed();
});

test('sync mode broadcasts one event per recipient without the queue', function () {
    config(['queue.default' => 'database']);
    Event::fake([UserNotificationCreated::class]);

    $users = User::factory()->count(3)->create();

    app(NotificationService::class)->send($users, NotificationType::System, 'Direct', 'Zonder queue', queue: false);

    Event::assertDispatchedTimes(UserNotificationCreated::class, 3);
    $this->assertDatabaseCount('jobs', 0);
});

test('the fan-out job dispatches the realtime event per row', function () {
    Event::fake([UserNotificationCreated::class]);

    $notification = AppNotification::factory()->create();
    UserNotification::factory()->count(2)->create(['app_notification_id' => $notification->id]);

    (new FanOutNotificationJob($notification->id))->handle();

    Event::assertDispatchedTimes(UserNotificationCreated::class, 2);
});

test('the realtime event targets the private user channel with the inbox payload', function () {
    $user = User::factory()->create();
    $row = UserNotification::factory()->create([
        'user_id' => $user->id,
        'app_notification_id' => AppNotification::factory()->create([
            'type' => NotificationType::Order,
            'title' => 'Bestelling betaald',
            'message' => 'Order #12',
            'data' => ['link_url' => 'https://maison.test/orders/12', 'link_label' => 'Bekijk'],
        ])->id,
    ]);

    $event = new UserNotificationCreated($row);

    expect($event->broadcastOn()[0]->name)->toBe("private-App.Models.User.{$user->id}")
        ->and($event->broadcastAs())->toBe('notification.created')
        ->and($event->broadcastWith())->toMatchArray([
            'id' => $row->id,
            'type' => 'order',
            'title' => 'Bestelling betaald',
            'message' => 'Order #12',
            'href' => 'https://maison.test/orders/12',
            'link_label' => 'Bekijk',
            'read' => false,
        ]);
});

test('the recipient pair stays unique even after a soft delete', function () {
    $row = UserNotification::factory()->create();
    $row->delete();

    expect(fn () => UserNotification::query()->create([
        'app_notification_id' => $row->app_notification_id,
        'user_id' => $row->user_id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('the notify helpers delegate to the service', function () {
    Queue::fake();

    $user = User::factory()->create();
    $others = User::factory()->count(2)->create();

    expect(notify_user($user, NotificationType::Success, 'Een', 'Bericht'))->toBeInstanceOf(AppNotification::class)
        ->and(notify_users($others, NotificationType::Info, 'Twee', 'Bericht'))->toBeInstanceOf(AppNotification::class)
        ->and($user->userNotifications()->count())->toBe(1)
        ->and(UserNotification::query()->count())->toBe(3);
});
