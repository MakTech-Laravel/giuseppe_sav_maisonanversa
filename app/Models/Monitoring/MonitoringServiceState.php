<?php

namespace App\Models\Monitoring;

use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;
use Illuminate\Database\Eloquent\Model;

class MonitoringServiceState extends Model
{
    protected $table = 'monitoring_service_states';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'status',
        'container_status',
        'consecutive_failures',
        'restart_count',
        'auto_restart_attempts_window',
        'last_success_at',
        'last_failure_at',
        'last_checked_at',
        'last_restart_at',
        'window_started_at',
        'auto_recovery_enabled',
        'restart_in_progress',
        'last_latency_ms',
        'last_error_category',
        'last_error_message',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consecutive_failures' => 'integer',
            'restart_count' => 'integer',
            'auto_restart_attempts_window' => 'integer',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'last_restart_at' => 'datetime',
            'window_started_at' => 'datetime',
            'auto_recovery_enabled' => 'boolean',
            'restart_in_progress' => 'boolean',
            'last_latency_ms' => 'integer',
            'meta' => 'array',
        ];
    }

    public function service(): MonitoredServiceId
    {
        return MonitoredServiceId::from($this->service_id);
    }

    public function healthStatus(): HealthStatus
    {
        return HealthStatus::tryFrom((string) $this->status) ?? HealthStatus::Unknown;
    }

    public static function forService(MonitoredServiceId $service): self
    {
        return static::query()->firstOrCreate(
            ['service_id' => $service->value],
            [
                'status' => HealthStatus::Unknown->value,
                'auto_recovery_enabled' => (bool) config('server_monitoring.auto_recovery_enabled_default', false),
            ],
        );
    }
}
