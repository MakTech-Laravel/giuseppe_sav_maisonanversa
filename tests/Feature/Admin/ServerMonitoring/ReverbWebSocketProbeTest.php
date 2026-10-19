<?php

use App\Monitoring\Enums\ErrorCategory;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Probes\ReverbWebSocketProbe;
use App\Monitoring\ServiceRegistry;

test('missing reverb configuration returns unknown without connecting', function () {
    config([
        'server_monitoring.services.reverb.host' => '',
        'server_monitoring.services.reverb.app_key' => '',
        'server_monitoring.services.main-app.health_url' => 'https://app.example.test',
    ]);

    $result = app(ReverbWebSocketProbe::class)->probe();

    expect($result->status)->toBe(HealthStatus::Unknown)
        ->and($result->errorCategory)->toBe(ErrorCategory::Configuration)
        ->and($result->websocketOk)->toBeNull();
});

test('service registry allowlist includes configured probe hosts only', function () {
    config([
        'server_monitoring.services.main-app.health_url' => 'https://app.example.test',
        'server_monitoring.services.reverb.host' => 'ws.example.test',
        'server_monitoring.services.reverb.internal_host' => '',
    ]);

    $hosts = app(ServiceRegistry::class)->allowedProbeHosts();

    expect($hosts)->toContain('app.example.test')
        ->and($hosts)->toContain('ws.example.test')
        ->and($hosts)->not->toContain('evil.example.test');
});

test('tls verify failures surface a certificate-oriented error', function () {
    config([
        'server_monitoring.tls_verify' => true,
        'server_monitoring.probe_timeout_seconds' => 2,
        'server_monitoring.services.main-app.health_url' => 'https://app.example.test',
        'server_monitoring.services.reverb.host' => '127.0.0.1',
        'server_monitoring.services.reverb.port' => 1,
        'server_monitoring.services.reverb.scheme' => 'https',
        'server_monitoring.services.reverb.app_key' => 'test-key',
    ]);

    $result = app(ReverbWebSocketProbe::class)->probe();

    expect($result->status)->toBe(HealthStatus::Unreachable)
        ->and($result->websocketOk)->toBeFalse()
        ->and(in_array($result->errorCategory, [ErrorCategory::Tls, ErrorCategory::Timeout], true))->toBeTrue();
});
