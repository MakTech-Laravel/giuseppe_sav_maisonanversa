<?php

namespace App\Console\Commands\Monitoring;

use App\Models\Monitoring\MonitoringServiceState;
use App\Monitoring\AutoRecoveryService;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\HealthCheckRunner;
use App\Monitoring\MonitoringNotifier;
use App\Monitoring\UptimeCalculator;
use Illuminate\Console\Command;

class RunHealthChecksCommand extends Command
{
    protected $signature = 'monitoring:run-checks {--no-recovery : Skip automatic recovery}';

    protected $description = 'Run health checks for monitored services (Reverb)';

    public function handle(
        HealthCheckRunner $runner,
        AutoRecoveryService $recovery,
        UptimeCalculator $uptime,
        MonitoringNotifier $notifier,
    ): int {
        $results = $runner->runAll();

        if ($results === []) {
            $this->warn('Another monitoring run is already in progress.');

            return self::SUCCESS;
        }

        foreach ($results as $serviceId => $probe) {
            $this->line(sprintf(
                '%s: %s (%sms)',
                $serviceId,
                $probe->status->value,
                $probe->latencyMs ?? 'n/a',
            ));
        }

        $allMonitoredStale = true;
        foreach (MonitoredServiceId::monitored() as $service) {
            $state = MonitoringServiceState::forService($service);
            if (! $uptime->isStale($state->last_checked_at)) {
                $allMonitoredStale = false;
                break;
            }
        }

        if ($allMonitoredStale) {
            $notifier->monitoringWorkerUnavailable();
        }

        if (! $this->option('no-recovery')) {
            $recovery->processAfterChecks();
        }

        return self::SUCCESS;
    }
}
