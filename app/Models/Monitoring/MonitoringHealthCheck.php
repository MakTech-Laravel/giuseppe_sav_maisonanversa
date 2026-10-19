<?php

namespace App\Models\Monitoring;

use Illuminate\Database\Eloquent\Model;

class MonitoringHealthCheck extends Model
{
    protected $table = 'monitoring_health_checks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'status',
        'http_status',
        'websocket_ok',
        'latency_ms',
        'error_category',
        'error_message',
        'details',
        'checked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'websocket_ok' => 'boolean',
            'latency_ms' => 'integer',
            'details' => 'array',
            'checked_at' => 'datetime',
        ];
    }
}
