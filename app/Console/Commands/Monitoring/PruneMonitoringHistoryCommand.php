<?php

namespace App\Console\Commands\Monitoring;

use App\Models\Monitoring\MonitoringHealthCheck;
use Illuminate\Console\Command;

class PruneMonitoringHistoryCommand extends Command
{
    protected $signature = 'monitoring:prune-history {--days= : Override retention days}';

    protected $description = 'Prune old monitoring health-check rows';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('server_monitoring.history_retention_days', 30));
        $cutoff = now()->subDays(max(1, $days));

        $deleted = MonitoringHealthCheck::query()
            ->where('checked_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} health-check rows older than {$days} days.");

        return self::SUCCESS;
    }
}
