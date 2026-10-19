<?php

namespace App\Monitoring\Enums;

enum RestartOutcome: string
{
    case Requested = 'requested';
    case InProgress = 'in_progress';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case SucceededUnhealthy = 'succeeded_unhealthy';
    case Rejected = 'rejected';
    case CoolifyUnavailable = 'coolify_unavailable';
}
