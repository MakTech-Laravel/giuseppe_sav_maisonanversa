<?php

use App\Enums\PermissionEnum;
use App\Models\Monitoring\MonitoringRestart;
use App\Models\Monitoring\MonitoringServiceState;
use App\Models\User;
use App\Monitoring\DTO\ProbeResult;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\Enums\RestartOutcome;
use App\Monitoring\HealthCheckRunner;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'maison.admin_type_grants_all_permissions' => false,
        'server_monitoring.coolify.base_url' => 'https://coolify.example.test/api/v1',
        'server_monitoring.coolify.token' => 'test-token',
        'server_monitoring.services.main-app.coolify_uuid' => 'main-uuid-1111',
        'server_monitoring.services.main-app.coolify_resource_type' => 'application',
        'server_monitoring.services.reverb.coolify_uuid' => 'reverb-uuid-2222',
        'server_monitoring.services.reverb.coolify_resource_type' => 'application',
        'server_monitoring.post_restart_verify_delay_seconds' => 0,
        'server_monitoring.restart_cooldown_seconds' => 0,
    ]);

    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->staff = User::factory()->admin()->create();
    $this->staff->givePermissionTo(PermissionEnum::SERVERS_MAINTENANCE_RESTART->value);
});

test('unauthorized staff cannot restart services', function () {
    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo(PermissionEnum::DASHBOARD_VIEW->value);

    $this->actingAs($staff)
        ->postJson(route('admin.servers-maintenance.restart', ['locale' => 'nl', 'service' => 'main-app']))
        ->assertForbidden();
});

test('main app restart does not call reverb coolify endpoint', function () {
    Http::fake([
        'https://coolify.example.test/api/v1/applications/main-uuid-1111/restart' => Http::response([
            'message' => 'Restart request queued.',
            'deployment_uuid' => 'dep-main',
        ], 200),
        'https://coolify.example.test/api/v1/applications/main-uuid-1111' => Http::response([
            'uuid' => 'main-uuid-1111',
            'status' => 'running',
        ], 200),
        'https://coolify.example.test/api/v1/applications/reverb-uuid-2222/*' => Http::response(['message' => 'should not hit'], 500),
    ]);

    $this->mock(HealthCheckRunner::class, function ($mock) {
        $mock->shouldReceive('runOne')
            ->once()
            ->with(Mockery::on(fn ($service) => $service === MonitoredServiceId::MainApp))
            ->andReturn(new ProbeResult(status: HealthStatus::Healthy, latencyMs: 10, httpStatus: 200));
    });

    $this->actingAs($this->staff)
        ->postJson(route('admin.servers-maintenance.restart', ['locale' => 'nl', 'service' => 'main-app']))
        ->assertOk()
        ->assertJsonPath('data.service', 'main-app')
        ->assertJsonPath('data.outcome', RestartOutcome::Succeeded->value);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'main-uuid-1111/restart'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'reverb-uuid-2222/restart'));

    expect(MonitoringRestart::query()->where('service_id', 'main-app')->count())->toBe(1)
        ->and(MonitoringRestart::query()->where('service_id', 'reverb')->count())->toBe(0);
});

test('reverb restart does not call main app coolify endpoint', function () {
    Http::fake([
        'https://coolify.example.test/api/v1/applications/reverb-uuid-2222/restart' => Http::response([
            'message' => 'Restart request queued.',
            'deployment_uuid' => 'dep-reverb',
        ], 200),
        'https://coolify.example.test/api/v1/applications/reverb-uuid-2222' => Http::response([
            'uuid' => 'reverb-uuid-2222',
            'status' => 'running',
        ], 200),
    ]);

    $this->mock(HealthCheckRunner::class, function ($mock) {
        $mock->shouldReceive('runOne')
            ->once()
            ->with(Mockery::on(fn ($service) => $service === MonitoredServiceId::Reverb))
            ->andReturn(new ProbeResult(
                status: HealthStatus::Healthy,
                latencyMs: 8,
                httpStatus: 101,
                websocketOk: true,
            ));
    });

    $this->actingAs($this->staff)
        ->postJson(route('admin.servers-maintenance.restart', ['locale' => 'nl', 'service' => 'reverb']))
        ->assertOk()
        ->assertJsonPath('data.service', 'reverb');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'reverb-uuid-2222/restart'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'main-uuid-1111/restart'));
});

test('coolify auth failure is reported without claiming success', function () {
    Http::fake([
        'https://coolify.example.test/api/v1/applications/main-uuid-1111/restart' => Http::response([
            'message' => 'Unauthenticated.',
        ], 401),
    ]);

    $this->actingAs($this->staff)
        ->postJson(route('admin.servers-maintenance.restart', ['locale' => 'nl', 'service' => 'main-app']))
        ->assertStatus(502)
        ->assertJsonPath('data.outcome', RestartOutcome::Failed->value);
});

test('duplicate restart while in progress is rejected', function () {
    MonitoringServiceState::forService(MonitoredServiceId::MainApp)->update([
        'restart_in_progress' => true,
    ]);

    $this->actingAs($this->staff)
        ->postJson(route('admin.servers-maintenance.restart', ['locale' => 'nl', 'service' => 'main-app']))
        ->assertStatus(409)
        ->assertJsonPath('data.outcome', RestartOutcome::Rejected->value);
});

test('restart both requires confirmation and reports both outcomes', function () {
    Http::fake([
        'https://coolify.example.test/api/v1/applications/*/restart' => Http::response([
            'message' => 'Restart request queued.',
            'deployment_uuid' => 'dep',
        ], 200),
        'https://coolify.example.test/api/v1/applications/*' => Http::response([
            'status' => 'running',
        ], 200),
    ]);

    $this->mock(HealthCheckRunner::class, function ($mock) {
        $mock->shouldReceive('runOne')->twice()->andReturn(
            new ProbeResult(status: HealthStatus::Healthy, latencyMs: 5, httpStatus: 200),
        );
    });

    $this->actingAs($this->staff)
        ->postJson(route('admin.servers-maintenance.restart-both', ['locale' => 'nl']), [
            'confirm' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.main-app.outcome', RestartOutcome::Succeeded->value)
        ->assertJsonPath('data.reverb.outcome', RestartOutcome::Succeeded->value);
});

test('missing coolify config returns unavailable', function () {
    config([
        'server_monitoring.coolify.base_url' => '',
        'server_monitoring.coolify.token' => '',
    ]);

    $this->actingAs($this->staff)
        ->postJson(route('admin.servers-maintenance.restart', ['locale' => 'nl', 'service' => 'main-app']))
        ->assertStatus(503)
        ->assertJsonPath('data.outcome', RestartOutcome::CoolifyUnavailable->value);
});
