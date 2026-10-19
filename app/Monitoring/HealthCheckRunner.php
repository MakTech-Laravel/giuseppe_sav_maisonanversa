<?php

namespace App\Monitoring;

use App\Models\Monitoring\MonitoringHealthCheck;
use App\Models\Monitoring\MonitoringIncident;
use App\Models\Monitoring\MonitoringServiceState;
use App\Monitoring\Coolify\CoolifyClient;
use App\Monitoring\DTO\ProbeResult;
use App\Monitoring\Enums\ErrorCategory;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\Probes\MainAppHttpProbe;
use App\Monitoring\Probes\ReverbWebSocketProbe;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckRunner
{
    public function __construct(
        private MainAppHttpProbe $mainAppProbe,
        private ReverbWebSocketProbe $reverbProbe,
        private CoolifyClient $coolify,
        private MonitoringNotifier $notifier,
    ) {}

    /**
     * @return array<string, ProbeResult>
     */
    public function runAll(): array
    {
        $lock = Cache::lock('monitoring:run-checks', 55);

        if (! $lock->get()) {
            return [];
        }

        try {
            $results = [];

            foreach (MonitoredServiceId::monitored() as $service) {
                $results[$service->value] = $this->runOne($service);
            }

            return $results;
        } finally {
            $lock->release();
        }
    }

    public function runOne(MonitoredServiceId $service): ProbeResult
    {
        $probe = match ($service) {
            MonitoredServiceId::MainApp => $this->mainAppProbe->probe(),
            MonitoredServiceId::Reverb => $this->reverbProbe->probe(),
        };

        $coolifyMeta = $this->coolify->getResourceStatus($service);
        $containerStatus = $coolifyMeta['ok'] ?? false
            ? ($coolifyMeta['status'] ?? null)
            : null;

        $now = now();

        DB::transaction(function () use ($service, $probe, $containerStatus, $coolifyMeta, $now): void {
            MonitoringHealthCheck::query()->create([
                'service_id' => $service->value,
                'status' => $probe->status->value,
                'http_status' => $probe->httpStatus,
                'websocket_ok' => $probe->websocketOk,
                'latency_ms' => $probe->latencyMs,
                'error_category' => $probe->errorCategory === ErrorCategory::None
                    ? null
                    : $probe->errorCategory->value,
                'error_message' => $probe->errorMessage,
                'details' => array_merge($probe->details, [
                    'coolify' => [
                        'ok' => $coolifyMeta['ok'] ?? false,
                        'status' => $coolifyMeta['status'] ?? null,
                        'error' => $coolifyMeta['error_message'] ?? null,
                    ],
                ]),
                'checked_at' => $now,
            ]);

            $state = MonitoringServiceState::forService($service);
            $wasHealthy = $state->healthStatus() === HealthStatus::Healthy;
            $isHealthy = $probe->isHealthy();

            $state->status = $probe->status->value;
            $state->container_status = is_string($containerStatus) ? $containerStatus : $state->container_status;
            $state->last_checked_at = $now;
            $state->last_latency_ms = $probe->latencyMs;
            $state->last_error_category = $probe->errorCategory === ErrorCategory::None
                ? null
                : $probe->errorCategory->value;
            $state->last_error_message = $probe->errorMessage;
            $state->meta = array_merge($state->meta ?? [], [
                'last_probe_details' => $probe->details,
                'coolify_status' => $coolifyMeta['status'] ?? null,
            ]);

            if ($isHealthy) {
                $state->consecutive_failures = 0;
                $state->last_success_at = $now;
            } else {
                $state->consecutive_failures = ((int) $state->consecutive_failures) + 1;
                $state->last_failure_at = $now;
            }

            $state->save();

            if (! $isHealthy && $wasHealthy) {
                MonitoringIncident::query()->create([
                    'service_id' => $service->value,
                    'status' => 'open',
                    'summary' => "{$service->label()} became {$probe->status->value}",
                    'error_category' => $probe->errorCategory->value,
                    'started_at' => $now,
                    'meta' => ['probe' => $probe->details],
                ]);

                $this->notifier->serviceDown($service, $probe);
            }

            if ($isHealthy && ! $wasHealthy && $state->last_failure_at !== null) {
                $open = MonitoringIncident::query()
                    ->where('service_id', $service->value)
                    ->where('status', 'open')
                    ->latest('started_at')
                    ->first();

                if ($open !== null) {
                    $open->update([
                        'status' => 'resolved',
                        'resolved_at' => $now,
                    ]);
                }

                $this->notifier->serviceRecovered($service);
            }
        });

        return $probe;
    }
}
