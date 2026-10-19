<?php

namespace App\Models\Monitoring;

use Illuminate\Database\Eloquent\Model;

class MonitoringIncident extends Model
{
    protected $table = 'monitoring_incidents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'status',
        'summary',
        'error_category',
        'started_at',
        'resolved_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
