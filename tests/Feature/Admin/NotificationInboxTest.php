<?php

use App\Enums\NotificationType;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\UserNotification;
use Inertia\Testing\AssertableInertia as Assert;

function inboxRow(User $user, array $notification = [], array $row = []): UserNotification
{
    return UserNotification::factory()->create([
        'user_id' => $user->id,
        'app_notification_id' => AppNotification::factory()->create($notification)->id,
        ...$row,
    ]);
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('guests are redirected away from the inbox', function () {
    $this->get(localized('admin.notifications.index'))->assertRedirect();
});

test('customers cannot open the admin inbox', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get(localized('admin.notifications.index'))
        ->assertForbidden();
});

test('the inbox lists only the acting admin rows with filters and pagination', function () {
    inboxRow($this->admin, ['title' => 'Nieuwe bestelling', 'type' => NotificationType::Order]);
    inboxRow($this->admin, ['title' => 'Systeemupdate', 'type' => NotificationType::System], ['read_at' => now()]);
    inboxRow(User::factory()->admin()->create(), ['title' => 'Niet van mij']);

    $this->actingAs($this->admin)
        ->get(localized('admin.notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/notifications/index')
            ->has('rows.data', 2)
            ->where('unreadCount', 1)
            ->where('filters.status', 'all'));

    $this->actingAs($this->admin)
        ->get(localized('admin.notifications.index', ['status' => 'unread']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.title', 'Nieuwe bestelling'));

    $this->actingAs($this->admin)
        ->get(localized('admin.notifications.index', ['type' => 'system']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.title', 'Systeemupdate'));

    $this->actingAs($this->admin)
        ->get(localized('admin.notifications.index', ['search' => 'bestel']))
        ->assertInertia(fn (Assert $page) => $page->has('rows.data', 1));
});

test('the inbox paginates twenty rows per page', function () {
    foreach (range(1, 21) as $i) {
        inboxRow($this->admin);
    }

    $this->actingAs($this->admin)
        ->get(localized('admin.notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 20)
            ->where('rows.last_page', 2));
});

test('opening a notification marks it as read', function () {
    $row = inboxRow($this->admin, ['title' => 'Lees mij']);

    $this->actingAs($this->admin)
        ->get(localized('admin.notifications.show', ['userNotification' => $row->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/notifications/show')
            ->where('notification.title', 'Lees mij')
            ->where('notification.read', true));

    expect($row->fresh()->read_at)->not->toBeNull();
});

test('another user notification returns 404', function () {
    $row = inboxRow(User::factory()->admin()->create());

    $this->actingAs($this->admin);

    $this->get(localized('admin.notifications.show', ['userNotification' => $row->id]))->assertNotFound();
    $this->post(localized('admin.notifications.read', ['userNotification' => $row->id]))->assertNotFound();
    $this->delete(localized('admin.notifications.destroy', ['userNotification' => $row->id]))->assertNotFound();

    expect($row->fresh()->read_at)->toBeNull()
        ->and($row->fresh()->trashed())->toBeFalse();
});

test('admins can mark one or all notifications as read', function () {
    $first = inboxRow($this->admin);
    $second = inboxRow($this->admin);
    $foreign = inboxRow(User::factory()->admin()->create());

    $this->actingAs($this->admin)
        ->post(localized('admin.notifications.read', ['userNotification' => $first->id]))
        ->assertRedirect();

    expect($first->fresh()->read_at)->not->toBeNull()
        ->and($second->fresh()->read_at)->toBeNull();

    $this->actingAs($this->admin)
        ->post(localized('admin.notifications.read-all'))
        ->assertRedirect();

    expect($second->fresh()->read_at)->not->toBeNull()
        ->and($foreign->fresh()->read_at)->toBeNull();
});

test('deleting soft-deletes the row', function () {
    $row = inboxRow($this->admin);

    $this->actingAs($this->admin)
        ->delete(localized('admin.notifications.destroy', ['userNotification' => $row->id]))
        ->assertRedirect();

    expect($row->fresh()->trashed())->toBeTrue();
});

test('the shared notifications prop carries the unread count and preview', function () {
    inboxRow($this->admin, ['title' => 'Eerste']);
    inboxRow($this->admin, ['title' => 'Tweede'], ['read_at' => now()]);

    $this->actingAs($this->admin)
        ->get(localized('admin.notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notifications.unreadCount', 1)
            ->has('notifications.preview', 2)
            ->where('notifications.preview.0.title', 'Tweede'));
});
