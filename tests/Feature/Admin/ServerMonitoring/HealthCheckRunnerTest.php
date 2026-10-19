<?php

use App\Models\Monitoring\MonitoringIncident;
use App\Models\Monitoring\MonitoringServiceState;
use App\Monitoring\Coolify\CoolifyClient;
use App\Monitoring\DTO\ProbeResult;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\HealthCheckRunner;
use App\Monitoring\MonitoringNotifier;
use App\Monitoring\Probes\MainAppHttpProbe;
use App\Monitoring\Probes\ReverbWebSocketProbe;
use Illuminate\Support\Facades\Cache;

test('run all probes only reverb', function () {
    $this->mock(MainAppHttpProbe::class, function ($mock) {
        $mock->shouldReceive('probe')->never();
    });

    $this->mock(ReverbWebSocketProbe::class, function ($mock) {
        $mock->shouldReceive('probe')->once()->andReturn(new ProbeResult(
            status: HealthStatus::Healthy,
            latencyMs: 15,
            httpStatus: 101,
            websocketOk: true,
        ));
    });

    $this->mock(CoolifyClient::class, function ($mock) {
        $mock->shouldReceive('getResourceStatus')->once()->andReturn(['ok' => false]);
    });

    $this->mock(MonitoringNotifier::class, function ($mock) {
        $mock->shouldReceive('serviceDown')->never();
        $mock->shouldReceive('serviceRecovered')->never();
    });

    MonitoringServiceState::forService(MonitoredServiceId::MainApp)->update([
        'status' => HealthStatus::Healthy->value,
    ]);
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update([
        'status' => HealthStatus::Unknown->value,
    ]);

    $results = app(HealthCheckRunner::class)->runAll();

    expect($results)->toHaveKey('reverb')
        ->and($results)->not->toHaveKey('main-app')
        ->and($results['reverb']->status)->toBe(HealthStatus::Healthy)
        ->and(MonitoringServiceState::forService(MonitoredServiceId::Reverb)->status)->toBe('healthy')
        ->and(MonitoringServiceState::forService(MonitoredServiceId::MainApp)->status)->toBe('healthy');
});

test('reverb websocket failure records reverb incident without probing main app', function () {
    $this->mock(MainAppHttpProbe::class, function ($mock) {
        $mock->shouldReceive('probe')->never();
    });

    $this->mock(ReverbWebSocketProbe::class, function ($mock) {
        $mock->shouldReceive('probe')->once()->andReturn(new ProbeResult(
            status: HealthStatus::Unreachable,
            latencyMs: 30,
            websocketOk: false,
        ));
    });

    $this->mock(CoolifyClient::class, function ($mock) {
        $mock->shouldReceive('getResourceStatus')->once()->andReturn(['ok' => false]);
    });

    $this->mock(MonitoringNotifier::class, function ($mock) {
        $mock->shouldReceive('serviceDown')->once();
    });

    MonitoringServiceState::forService(MonitoredServiceId::MainApp)->update([
        'status' => HealthStatus::Healthy->value,
    ]);
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update([
        'status' => HealthStatus::Healthy->value,
    ]);

    app(HealthCheckRunner::class)->runAll();

    expect(MonitoringServiceState::forService(MonitoredServiceId::MainApp)->status)->toBe('healthy')
        ->and(MonitoringServiceState::forService(MonitoredServiceId::Reverb)->status)->toBe('unreachable')
        ->and(MonitoringIncident::query()->where('service_id', 'reverb')->where('status', 'open')->count())->toBe(1)
        ->and(MonitoringIncident::query()->where('service_id', 'main-app')->count())->toBe(0);
});

test('concurrent runners skip when lock is held', function () {
    Cache::lock('monitoring:run-checks', 55)->get();

    $this->mock(MainAppHttpProbe::class, function ($mock) {
        $mock->shouldReceive('probe')->never();
    });
    $this->mock(ReverbWebSocketProbe::class, function ($mock) {
        $mock->shouldReceive('probe')->never();
    });

    expect(app(HealthCheckRunner::class)->runAll())->toBe([]);
});
