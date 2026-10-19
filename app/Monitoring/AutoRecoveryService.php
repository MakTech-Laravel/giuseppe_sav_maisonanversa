<?php

namespace App\Monitoring;

use App\Models\Monitoring\MonitoringServiceState;
use App\Models\Monitoring\MonitoringSetting;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\Enums\RestartOutcome;
use Illuminate\Support\Facades\Cache;

class AutoRecoveryService
{
    public function __construct(
        private RestartService $restarts,
        private MonitoringNotifier $notifier,
    ) {}

    public function processAfterChecks(): void
    {
        if (! $this->globalEnabled()) {
            return;
        }

        $lock = Cache::lock('monitoring:auto-recovery', 55);

        if (! $lock->get()) {
            return;
        }

        try {
            foreach (MonitoredServiceId::monitored() as $service) {
                $this->maybeRestart($service);
            }
        } finally {
            $lock->release();
        }
    }

    public function maybeRestart(MonitoredServiceId $service): void
    {
        if (! $this->globalEnabled()) {
            return;
        }

        if (! in_array($service, MonitoredServiceId::monitored(), true)) {
            return;
        }

        $state = MonitoringServiceState::forService($service);

        if (! $state->auto_recovery_enabled) {
            return;
        }

        // Already restarting — never start a second one.
        if ($state->restart_in_progress) {
            return;
        }

        // Only act when the probe says down / broken (not healthy, not unknown/config).
        if (! $this->shouldAutoRestart($state->healthStatus())) {
            return;
        }

        $this->notifier->autoRestartInitiated($service);

        $result = $this->restarts->restart(
            service: $service,
            trigger: 'auto',
            reason: 'Auto restart: service unhealthy or stopped',
            verifyHealth: true,
        );

        $outcome = $result['outcome'];

        // Overlap / Coolify cooldown rejection — try again on a later check cycle.
        if (in_array($outcome, [RestartOutcome::Rejected, RestartOutcome::CoolifyUnavailable], true)) {
            if ($outcome === RestartOutcome::CoolifyUnavailable) {
                $this->notifier->autoRestartFailed($service, $result['message']);
            }

            return;
        }

        if (! in_array($outcome, [RestartOutcome::Succeeded, RestartOutcome::SucceededUnhealthy], true)) {
            $this->notifier->autoRestartFailed($service, $result['message']);
        }
    }

    public function globalEnabled(): bool
    {
        return MonitoringSetting::getBool(
            'auto_recovery_global',
            (bool) config('server_monitoring.auto_recovery_enabled_default', false),
        );
    }

    public function setGlobalEnabled(bool $enabled): void
    {
        MonitoringSetting::put('auto_recovery_global', $enabled);
    }

    private function shouldAutoRestart(HealthStatus $status): bool
    {
        return in_array($status, [
            HealthStatus::Unhealthy,
            HealthStatus::Unreachable,
            HealthStatus::Degraded,
        ], true);
    }
}
