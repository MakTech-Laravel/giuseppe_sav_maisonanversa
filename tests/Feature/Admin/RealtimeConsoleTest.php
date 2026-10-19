<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Events\RealtimePrivateTestEvent;
use App\Events\ReverbPingEvent;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['maison.admin_type_grants_all_permissions' => false]);
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->staff = User::factory()->admin()->create();
    $this->staff->givePermissionTo(PermissionEnum::REALTIME_VIEW->value);
});

test('guests are redirected away from the realtime console', function () {
    $this->get(route('admin.realtime.index', ['locale' => 'nl']))->assertRedirect();
});

test('customers cannot open the realtime console', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get(route('admin.realtime.index', ['locale' => 'nl']))
        ->assertForbidden();
});

test('staff without realtime.view cannot use the console or its endpoints', function () {
    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo(PermissionEnum::DASHBOARD_VIEW->value);

    $this->actingAs($staff);

    $this->get(route('admin.realtime.index', ['locale' => 'nl']))->assertForbidden();
    $this->getJson(route('admin.realtime.health', ['locale' => 'nl']))->assertForbidden();
    $this->postJson(route('admin.realtime.ping', ['locale' => 'nl']))->assertForbidden();
    $this->postJson(route('admin.realtime.test-notification', ['locale' => 'nl']))->assertForbidden();
});

test('staff with realtime.view see the console page', function () {
    $this->actingAs($this->staff)
        ->get(route('admin.realtime.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/realtime/index'));
});

test('the admin role is granted realtime.view', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::ADMIN->value);

    expect($admin->can(PermissionEnum::REALTIME_VIEW->value))->toBeTrue();
});

test('the public ping dispatches a reverb ping event', function () {
    Event::fake([ReverbPingEvent::class]);

    $this->actingAs($this->staff)
        ->postJson(route('admin.realtime.ping', ['locale' => 'nl']), ['message' => 'hello'])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.channel', 'reverb-ping')
        ->assertJsonPath('data.event', 'ping')
        ->assertJsonPath('data.payload.message', 'hello');

    Event::assertDispatched(ReverbPingEvent::class, fn (ReverbPingEvent $event) => $event->message === 'hello'
        && $event->broadcastOn()[0]->name === 'reverb-ping');
});

test('the private test targets the acting user channel only', function () {
    Event::fake([RealtimePrivateTestEvent::class]);

    $this->actingAs($this->staff)
        ->postJson(route('admin.realtime.test-notification', ['locale' => 'nl']))
        ->assertOk()
        ->assertJsonPath('data.channel', "App.Models.User.{$this->staff->id}")
        ->assertJsonPath('data.event', 'realtime.test')
        ->assertJsonPath('data.payload.user_id', $this->staff->id);

    Event::assertDispatched(RealtimePrivateTestEvent::class, fn (RealtimePrivateTestEvent $event) => $event->user->is($this->staff)
        && $event->broadcastOn()[0]->name === "private-App.Models.User.{$this->staff->id}");
});

test('a failed publish is reported as a 502 instead of a server error', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
        'broadcasting.connections.reverb.options.host' => '127.0.0.1',
        'broadcasting.connections.reverb.options.port' => 1,
        'broadcasting.connections.reverb.options.scheme' => 'http',
        'broadcasting.connections.reverb.options.useTLS' => false,
    ]);

    $this->actingAs($this->staff)
        ->postJson(route('admin.realtime.ping', ['locale' => 'nl']))
        ->assertStatus(502)
        ->assertJsonPath('status', false);
});

test('the health endpoint reports the target without leaking the secret', function () {
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'abcdefghijklmnop',
        'broadcasting.connections.reverb.secret' => 'super-secret-value',
        'broadcasting.connections.reverb.options.host' => 'ws.maisonanversa.com',
        'broadcasting.connections.reverb.options.port' => 443,
        'broadcasting.connections.reverb.options.scheme' => 'https',
    ]);

    $response = $this->actingAs($this->staff)
        ->getJson(route('admin.realtime.health', ['locale' => 'nl']))
        ->assertOk()
        ->assertJsonPath('data.broadcast_connection', 'reverb')
        ->assertJsonPath('data.host', 'ws.maisonanversa.com')
        ->assertJsonPath('data.app_key_preview', 'abcdefgh...');

    expect($response->getContent())
        ->not->toContain('super-secret-value')
        ->not->toContain('abcdefghijklmnop');
});

test('the sidebar links the realtime console behind realtime.view', function () {
    $source = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    $title = strpos($source, "t('Realtime console')");
    $next = strpos($source, 'title:', $title + 1);

    expect($title)->not->toBeFalse()
        ->and(substr($source, $title, $next - $title))
        ->toContain('adminRealtime.index(locale)')
        ->toContain('PERMISSIONS.REALTIME.VIEW');
});
