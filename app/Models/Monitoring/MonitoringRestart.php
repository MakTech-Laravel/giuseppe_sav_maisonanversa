<?php

namespace App\Models\Monitoring;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringRestart extends Model
{
    protected $table = 'monitoring_restarts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'trigger',
        'outcome',
        'operator_id',
        'coolify_deployment_uuid',
        'reason',
        'health_verified',
        'meta',
        'requested_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'health_verified' => 'boolean',
            'meta' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
