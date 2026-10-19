<?php

namespace App\Monitoring;

use App\Models\Monitoring\MonitoringRestart;
use App\Models\Monitoring\MonitoringServiceState;
use App\Models\User;
use App\Monitoring\Coolify\CoolifyClient;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\Enums\RestartOutcome;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class RestartService
{
    public function __construct(
        private ServiceRegistry $registry,
        private CoolifyClient $coolify,
        private HealthCheckRunner $healthChecks,
    ) {}

    /**
     * @return array{outcome: RestartOutcome, restart_id?: int, message: string, health_verified?: bool|null, deployment_uuid?: string|null}
     */
    public function restart(
        MonitoredServiceId $service,
        string $trigger = 'manual',
        ?User $operator = null,
        ?string $reason = null,
        bool $verifyHealth = true,
    ): array {
        if (! $this->registry->isCoolifyConfigured($service)) {
            return [
                'outcome' => RestartOutcome::CoolifyUnavailable,
                'message' => 'Coolify API credentials or resource UUID are not configured for this service.',
            ];
        }

        $rateKey = "monitoring:restart-rate:{$service->value}";
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return [
                'outcome' => RestartOutcome::Rejected,
                'message' => 'Restart rate limit exceeded for this service. Try again later.',
            ];
        }

        $lock = Cache::lock("monitoring:restart:{$service->value}", 120);

        if (! $lock->get()) {
            return [
                'outcome' => RestartOutcome::Rejected,
                'message' => 'A restart is already in progress for this service.',
            ];
        }

        $claimed = false;

        try {
            $state = MonitoringServiceState::forService($service);

            if ($state->restart_in_progress) {
                return [
                    'outcome' => RestartOutcome::Rejected,
                    'message' => 'A restart is already marked in progress for this service.',
                ];
            }

            $cooldown = (int) config('server_monitoring.restart_cooldown_seconds', 120);
            if ($state->last_restart_at !== null && $state->last_restart_at->gt(now()->subSeconds($cooldown))) {
                return [
                    'outcome' => RestartOutcome::Rejected,
                    'message' => "Restart cooldown active ({$cooldown}s). Wait before retrying.",
                ];
            }

            $state->restart_in_progress = true;
            $state->save();
            $claimed = true;

            $restart = MonitoringRestart::query()->create([
                'service_id' => $service->value,
                'trigger' => $trigger,
                'outcome' => RestartOutcome::Requested->value,
                'operator_id' => $operator?->id,
                'reason' => $reason,
                'requested_at' => now(),
            ]);

            RateLimiter::hit($rateKey, 300);

            $api = $this->coolify->restart($service);

            if (! ($api['ok'] ?? false)) {
                $restart->update([
                    'outcome' => RestartOutcome::Failed->value,
                    'completed_at' => now(),
                    'meta' => ['error' => $api['error_message'] ?? 'Coolify restart failed'],
                ]);

                return [
                    'outcome' => RestartOutcome::Failed,
                    'restart_id' => $restart->id,
                    'message' => (string) ($api['error_message'] ?? 'Coolify restart failed'),
                ];
            }

            $restart->update([
                'outcome' => RestartOutcome::InProgress->value,
                'coolify_deployment_uuid' => $api['deployment_uuid'] ?? null,
                'meta' => ['coolify_message' => $api['message'] ?? null],
            ]);

            $state->last_restart_at = now();
            $state->restart_count = ((int) $state->restart_count) + 1;
            $state->save();

            $healthVerified = null;
            $outcome = RestartOutcome::Succeeded;

            if ($verifyHealth) {
                $delay = (int) config('server_monitoring.post_restart_verify_delay_seconds', 15);
                if ($delay > 0 && ! app()->runningUnitTests()) {
                    sleep(min($delay, 30));
                }

                $probe = $this->healthChecks->runOne($service);
                $healthVerified = $probe->isHealthy();
                $outcome = $healthVerified
                    ? RestartOutcome::Succeeded
                    : RestartOutcome::SucceededUnhealthy;
            }

            $restart->update([
                'outcome' => $outcome->value,
                'health_verified' => $healthVerified,
                'completed_at' => now(),
            ]);

            return [
                'outcome' => $outcome,
                'restart_id' => $restart->id,
                'message' => match ($outcome) {
                    RestartOutcome::Succeeded => 'Restart completed and health checks passed.',
                    RestartOutcome::SucceededUnhealthy => 'Restart was accepted but health checks still fail.',
                    default => 'Restart finished.',
                },
                'health_verified' => $healthVerified,
                'deployment_uuid' => $api['deployment_uuid'] ?? null,
            ];
        } finally {
            if ($claimed) {
                $state = MonitoringServiceState::forService($service);
                $state->restart_in_progress = false;
                $state->save();
            }

            $lock->release();
        }
    }

    /**
     * @return array{main-app: array<string, mixed>, reverb: array<string, mixed>}
     */
    public function restartBoth(?User $operator = null): array
    {
        return [
            'main-app' => $this->restart(MonitoredServiceId::MainApp, 'manual_both', $operator, 'Restart both requested'),
            'reverb' => $this->restart(MonitoredServiceId::Reverb, 'manual_both', $operator, 'Restart both requested'),
        ];
    }
}
