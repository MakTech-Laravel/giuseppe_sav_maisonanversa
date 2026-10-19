<?php

namespace App\Monitoring;

use App\Models\Monitoring\MonitoringHealthCheck;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class UptimeCalculator
{
    /**
     * Availability from observed checks only. Unmonitored gaps are excluded.
     *
     * @return array{availability_percent: float|null, healthy_checks: int, total_checks: int, window_hours: int}
     */
    public function forService(MonitoredServiceId $service, int $windowHours = 24): array
    {
        $since = Carbon::now()->subHours($windowHours);

        $checks = MonitoringHealthCheck::query()
            ->where('service_id', $service->value)
            ->where('checked_at', '>=', $since)
            ->get(['status']);

        $total = $checks->count();

        if ($total === 0) {
            return [
                'availability_percent' => null,
                'healthy_checks' => 0,
                'total_checks' => 0,
                'window_hours' => $windowHours,
            ];
        }

        $healthy = $checks->where('status', HealthStatus::Healthy->value)->count();

        return [
            'availability_percent' => round(($healthy / $total) * 100, 2),
            'healthy_checks' => $healthy,
            'total_checks' => $total,
            'window_hours' => $windowHours,
        ];
    }

    public function isStale(?CarbonInterface $lastCheckedAt): bool
    {
        if ($lastCheckedAt === null) {
            return true;
        }

        $staleAfter = (int) config('server_monitoring.stale_after_seconds', 120);

        return $lastCheckedAt->lt(now()->subSeconds($staleAfter));
    }
}
