<?php

use App\Models\Monitoring\MonitoringServiceState;
use App\Models\Monitoring\MonitoringSetting;
use App\Monitoring\AutoRecoveryService;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\Enums\RestartOutcome;
use App\Monitoring\MonitoringNotifier;
use App\Monitoring\RestartService;

beforeEach(function () {
    MonitoringSetting::put('auto_recovery_global', true);
});

test('auto restart triggers when reverb is unhealthy and enabled', function () {
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update([
        'status' => HealthStatus::Unhealthy->value,
        'consecutive_failures' => 1,
        'auto_recovery_enabled' => true,
        'restart_in_progress' => false,
    ]);

    $this->mock(RestartService::class, function ($mock) {
        $mock->shouldReceive('restart')
            ->once()
            ->withArgs(function ($service, $trigger) {
                return $service === MonitoredServiceId::Reverb && $trigger === 'auto';
            })
            ->andReturn([
                'outcome' => RestartOutcome::Succeeded,
                'message' => 'ok',
            ]);
    });

    $this->mock(MonitoringNotifier::class, function ($mock) {
        $mock->shouldReceive('autoRestartInitiated')->once();
        $mock->shouldReceive('autoRestartFailed')->never();
        $mock->shouldReceive('retryLimitReached')->never();
    });

    app(AutoRecoveryService::class)->processAfterChecks();
});

test('auto restart triggers when reverb is unreachable', function () {
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update([
        'status' => HealthStatus::Unreachable->value,
        'auto_recovery_enabled' => true,
        'restart_in_progress' => false,
    ]);

    $this->mock(RestartService::class, function ($mock) {
        $mock->shouldReceive('restart')->once()->andReturn([
            'outcome' => RestartOutcome::Succeeded,
            'message' => 'ok',
        ]);
    });

    $this->mock(MonitoringNotifier::class, function ($mock) {
        $mock->shouldReceive('autoRestartInitiated')->once();
    });

    app(AutoRecoveryService::class)->processAfterChecks();
});

test('auto restart does not run when reverb is healthy', function () {
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update([
        'status' => HealthStatus::Healthy->value,
        'auto_recovery_enabled' => true,
    ]);

    $this->mock(RestartService::class, function ($mock) {
        $mock->shouldReceive('restart')->never();
    });

    app(AutoRecoveryService::class)->processAfterChecks();
});

test('auto restart does not run for unknown status', function () {
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update([
        'status' => HealthStatus::Unknown->value,
        'auto_recovery_enabled' => true,
    ]);

    $this->mock(RestartService::class, function ($mock) {
        $mock->shouldReceive('restart')->never();
    });

    app(AutoRecoveryService::class)->processAfterChecks();
});

test('auto restart does not restart main app', function () {
    MonitoringServiceState::forService(MonitoredServiceId::MainApp)->update([
        'status' => HealthStatus::Unhealthy->value,
        'auto_recovery_enabled' => true,
    ]);
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update([
        'status' => HealthStatus::Healthy->value,
        'auto_recovery_enabled' => true,
    ]);

    $this->mock(RestartService::class, function ($mock) {
        $mock->shouldReceive('restart')->never();
    });

    app(AutoRecoveryService::class)->processAfterChecks();
});

test('auto restart skips when restart already in progress', function () {
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update([
        'status' => HealthStatus::Unhealthy->value,
        'auto_recovery_enabled' => true,
        'restart_in_progress' => true,
    ]);

    $this->mock(RestartService::class, function ($mock) {
        $mock->shouldReceive('restart')->never();
    });

    $this->mock(MonitoringNotifier::class, function ($mock) {
        $mock->shouldReceive('autoRestartInitiated')->never();
    });

    app(AutoRecoveryService::class)->processAfterChecks();
});

test('auto restart is disabled by default globally', function () {
    MonitoringSetting::query()->where('key', 'auto_recovery_global')->delete();
    config(['server_monitoring.auto_recovery_enabled_default' => false]);

    expect(app(AutoRecoveryService::class)->globalEnabled())->toBeFalse();
});
