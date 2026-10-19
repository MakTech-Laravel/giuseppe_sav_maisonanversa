<?php

namespace App\Monitoring;

use App\Enums\NotificationType;
use App\Enums\UserType;
use App\Models\User;
use App\Monitoring\DTO\ProbeResult;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Cache;

class MonitoringNotifier
{
    public function __construct(private NotificationService $notifications) {}

    public function serviceDown(MonitoredServiceId $service, ProbeResult $probe): void
    {
        $title = match ($service) {
            MonitoredServiceId::MainApp => 'Main application down',
            MonitoredServiceId::Reverb => $probe->websocketOk === false
                ? 'Reverb WebSocket unreachable'
                : 'Reverb unhealthy',
        };

        $this->notifyOnce(
            key: "down:{$service->value}",
            type: NotificationType::Alert,
            title: $title,
            message: $probe->errorMessage ?? "{$service->label()} failed its health check.",
        );
    }

    public function serviceRecovered(MonitoredServiceId $service): void
    {
        $this->notifyOnce(
            key: "recovered:{$service->value}",
            type: NotificationType::Success,
            title: "{$service->label()} recovered",
            message: "{$service->label()} is healthy again.",
        );
    }

    public function bothServicesFailing(): void
    {
        $this->notifyOnce(
            key: 'both-failing',
            type: NotificationType::Alert,
            title: 'Both services failing',
            message: 'Main application and Reverb are both unhealthy. Investigate shared dependencies before restarting both.',
        );
    }

    public function autoRestartInitiated(MonitoredServiceId $service): void
    {
        $this->notifyOnce(
            key: "auto-restart:{$service->value}",
            type: NotificationType::Warning,
            title: "Automatic restart initiated: {$service->label()}",
            message: "Auto-recovery requested a Coolify restart for {$service->label()}.",
        );
    }

    public function autoRestartFailed(MonitoredServiceId $service, string $reason): void
    {
        $this->notifyOnce(
            key: "auto-restart-failed:{$service->value}",
            type: NotificationType::Alert,
            title: "Automatic restart failed: {$service->label()}",
            message: $reason,
        );
    }

    public function retryLimitReached(MonitoredServiceId $service): void
    {
        $this->notifyOnce(
            key: "retry-limit:{$service->value}",
            type: NotificationType::Alert,
            title: "Recovery retry limit reached: {$service->label()}",
            message: "Automatic recovery stopped for {$service->label()}. Manual intervention required.",
        );
    }

    public function monitoringWorkerUnavailable(): void
    {
        $this->notifyOnce(
            key: 'worker-unavailable',
            type: NotificationType::Warning,
            title: 'Monitoring data stale',
            message: 'No recent health checks were recorded. Verify the scheduler and queue workers.',
        );
    }

    private function notifyOnce(
        string $key,
        NotificationType $type,
        string $title,
        string $message,
    ): void {
        $cooldown = (int) config('server_monitoring.notification_cooldown_seconds', 300);
        $cacheKey = "monitoring:notify:{$key}";

        if (! Cache::add($cacheKey, true, $cooldown)) {
            return;
        }

        $adminIds = User::query()
            ->where('type', UserType::Admin)
            ->pluck('id');

        if ($adminIds->isEmpty()) {
            return;
        }

        $this->notifications->send(
            userIds: $adminIds,
            type: $type,
            title: $title,
            message: $message,
            data: ['source' => 'server_monitoring'],
            queue: true,
        );
    }
}
