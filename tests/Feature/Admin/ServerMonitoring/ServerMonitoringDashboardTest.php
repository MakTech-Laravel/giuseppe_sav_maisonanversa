<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Monitoring\MonitoringHealthCheck;
use App\Models\Monitoring\MonitoringServiceState;
use App\Models\User;
use App\Monitoring\DTO\ProbeResult;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\Probes\ReverbWebSocketProbe;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['maison.admin_type_grants_all_permissions' => false]);
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->staff = User::factory()->admin()->create();
    $this->staff->givePermissionTo(PermissionEnum::DASHBOARD_VIEW->value);
});

test('guests are redirected away from servers maintenance', function () {
    $this->get(route('admin.servers-maintenance.index', ['locale' => 'nl']))->assertRedirect();
});

test('customers cannot open servers maintenance', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get(route('admin.servers-maintenance.index', ['locale' => 'nl']))
        ->assertForbidden();
});

test('any admin can read the servers maintenance dashboard', function () {
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update([
        'status' => HealthStatus::Unhealthy->value,
        'last_checked_at' => now(),
        'last_failure_at' => now(),
    ]);

    $this->actingAs($this->staff)
        ->get(route('admin.servers-maintenance.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/servers-maintenance/index')
            ->where('combined_status', 'reverb_degraded')
            ->where('realtime_status', 'degraded')
            ->where('services.reverb.status', 'unhealthy'));
});

test('the admin role is granted servers-maintenance.restart', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::ADMIN->value);

    expect($admin->can(PermissionEnum::SERVERS_MAINTENANCE_RESTART->value))->toBeTrue();
});

test('manual run checks probes only reverb and stores history', function () {
    config([
        'server_monitoring.services.reverb.host' => 'ws.example.test',
        'server_monitoring.services.reverb.port' => 443,
        'server_monitoring.services.reverb.scheme' => 'https',
        'server_monitoring.services.reverb.app_key' => 'test-key',
        'server_monitoring.coolify.base_url' => '',
        'server_monitoring.coolify.token' => '',
    ]);

    Http::fake();

    $this->mock(ReverbWebSocketProbe::class, function ($mock) {
        $mock->shouldReceive('probe')->once()->andReturn(new ProbeResult(
            status: HealthStatus::Healthy,
            latencyMs: 12,
            httpStatus: 101,
            websocketOk: true,
        ));
    });

    $this->actingAs($this->staff)
        ->post(route('admin.servers-maintenance.run-checks', ['locale' => 'nl']))
        ->assertRedirect();

    expect(MonitoringHealthCheck::query()->where('service_id', 'main-app')->count())->toBe(0)
        ->and(MonitoringHealthCheck::query()->where('service_id', 'reverb')->count())->toBe(1)
        ->and(MonitoringServiceState::forService(MonitoredServiceId::Reverb)->status)->toBe('healthy');
});
