<?php

namespace App\Monitoring\Enums;

enum HealthStatus: string
{
    case Healthy = 'healthy';
    case Unhealthy = 'unhealthy';
    case Unreachable = 'unreachable';
    case Unknown = 'unknown';
    case Degraded = 'degraded';
}
