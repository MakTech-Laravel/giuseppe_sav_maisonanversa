<?php

namespace App\Monitoring;

use App\Models\Monitoring\MonitoringHealthCheck;
use App\Models\Monitoring\MonitoringIncident;
use App\Models\Monitoring\MonitoringRestart;
use App\Models\Monitoring\MonitoringServiceState;
use App\Monitoring\Coolify\CoolifyClient;
use App\Monitoring\Enums\CombinedStatus;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;

final class DashboardPresenter
{
    public function __construct(
        private ServiceRegistry $registry,
        private UptimeCalculator $uptime,
        private CoolifyClient $coolify,
        private AutoRecoveryService $autoRecovery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        $reverb = $this->serviceCard(MonitoredServiceId::Reverb);
        $reverbStatus = HealthStatus::tryFrom((string) $reverb['status']) ?? HealthStatus::Unknown;
        $reverbStale = (bool) $reverb['is_stale'];

        $combined = $this->combinedStatusFromReverb($reverbStatus, $reverbStale);

        $realtimeStatus = match (true) {
            ($reverb['status'] ?? '') === HealthStatus::Healthy->value => 'healthy',
            ($reverb['status'] ?? '') === HealthStatus::Unknown->value => 'unknown',
            default => 'degraded',
        };

        return [
            'combined_status' => $combined->value,
            'realtime_status' => $realtimeStatus,
            'coolify_configured' => $this->coolify->isConfigured(),
            'auto_recovery' => [
                'global_enabled' => $this->autoRecovery->globalEnabled(),
                'cooldown_seconds' => (int) config('server_monitoring.restart_cooldown_seconds', 120),
            ],
            'services' => [
                // Main-app card kept for type compatibility; not actively monitored.
                'main-app' => $this->serviceCard(MonitoredServiceId::MainApp),
                'reverb' => $reverb,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceCard(MonitoredServiceId $service): array
    {
        $state = MonitoringServiceState::forService($service);
        $definition = $this->registry->definition($service);
        $uptime = $this->uptime->forService($service);
        $isStale = $this->uptime->isStale($state->last_checked_at);

        $recentChecks = MonitoringHealthCheck::query()
            ->where('service_id', $service->value)
            ->latest('checked_at')
            ->limit(20)
            ->get()
            ->map(fn (MonitoringHealthCheck $check): array => [
                'status' => $check->status,
                'http_status' => $check->http_status,
                'websocket_ok' => $check->websocket_ok,
                'latency_ms' => $check->latency_ms,
                'error_category' => $check->error_category,
                'error_message' => $check->error_message,
                'checked_at' => $check->checked_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $incidents = MonitoringIncident::query()
            ->where('service_id', $service->value)
            ->latest('started_at')
            ->limit(3)
            ->get()
            ->map(fn (MonitoringIncident $incident): array => [
                'status' => $incident->status,
                'summary' => $incident->summary,
                'error_category' => $incident->error_category,
                'started_at' => $incident->started_at?->toIso8601String(),
                'resolved_at' => $incident->resolved_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $restarts = MonitoringRestart::query()
            ->where('service_id', $service->value)
            ->latest('requested_at')
            ->limit(3)
            ->get()
            ->map(fn (MonitoringRestart $restart): array => [
                'trigger' => $restart->trigger,
                'outcome' => $restart->outcome,
                'reason' => $restart->reason,
                'health_verified' => $restart->health_verified,
                'operator_id' => $restart->operator_id,
                'coolify_deployment_uuid' => $restart->coolify_deployment_uuid,
                'requested_at' => $restart->requested_at?->toIso8601String(),
                'completed_at' => $restart->completed_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        $base = [
            'id' => $service->value,
            'label' => $service->label(),
            'status' => $isStale && $state->status !== HealthStatus::Unknown->value
                ? HealthStatus::Unknown->value
                : $state->status,
            'is_stale' => $isStale,
            'coolify_uuid' => $this->registry->coolifyUuid($service) !== ''
                ? $this->maskUuid($this->registry->coolifyUuid($service))
                : null,
            'coolify_configured' => $this->registry->isCoolifyConfigured($service),
            'container_status' => $state->container_status,
            'last_success_at' => $state->last_success_at?->toIso8601String(),
            'last_failure_at' => $state->last_failure_at?->toIso8601String(),
            'last_checked_at' => $state->last_checked_at?->toIso8601String(),
            'last_restart_at' => $state->last_restart_at?->toIso8601String(),
            'last_latency_ms' => $state->last_latency_ms,
            'restart_count' => (int) $state->restart_count,
            'consecutive_failures' => (int) $state->consecutive_failures,
            'restart_in_progress' => (bool) $state->restart_in_progress,
            'auto_recovery_enabled' => (bool) $state->auto_recovery_enabled,
            'last_error_category' => $state->last_error_category,
            'last_error_message' => $state->last_error_message,
            'uptime' => $uptime,
            'cpu' => null,
            'memory' => null,
            'recent_checks' => $recentChecks,
            'incidents' => $incidents,
            'restarts' => $restarts,
        ];

        if ($service === MonitoredServiceId::MainApp) {
            $base['public_url'] = (string) ($definition['public_url'] ?? '');
            $base['health_path'] = (string) ($definition['health_path'] ?? '/up');
        }

        if ($service === MonitoredServiceId::Reverb) {
            $base['hostname'] = (string) ($definition['host'] ?? '');
            $base['port'] = (int) ($definition['port'] ?? 443);
            $base['scheme'] = (string) ($definition['scheme'] ?? 'https');
            $base['websocket_status'] = $state->meta['last_probe_details']['handshake']
                ?? (($state->status === HealthStatus::Healthy->value) ? 'ok' : $state->status);
            $base['internal_host_configured'] = trim((string) ($definition['internal_host'] ?? '')) !== '';
        }

        return $base;
    }

    private function combinedStatusFromReverb(HealthStatus $reverb, bool $reverbStale): CombinedStatus
    {
        if ($reverbStale && $reverb === HealthStatus::Unknown) {
            return CombinedStatus::MonitoringUnavailable;
        }

        return match (true) {
            $reverb === HealthStatus::Healthy => CombinedStatus::BothHealthy,
            default => CombinedStatus::ReverbDegraded,
        };
    }

    private function maskUuid(string $uuid): string
    {
        if (strlen($uuid) <= 8) {
            return $uuid;
        }

        return substr($uuid, 0, 4).'…'.substr($uuid, -4);
    }
}
