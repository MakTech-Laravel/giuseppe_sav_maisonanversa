<?php

namespace App\Monitoring\Enums;

enum CombinedStatus: string
{
    case BothHealthy = 'both_healthy';
    case MainDegraded = 'main_degraded';
    case ReverbDegraded = 'reverb_degraded';
    case BothDegraded = 'both_degraded';
    case MonitoringUnavailable = 'monitoring_unavailable';
}
