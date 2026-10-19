<?php

namespace App\Monitoring\Enums;

enum ErrorCategory: string
{
    case None = 'none';
    case HttpFailure = 'http_failure';
    case Timeout = 'timeout';
    case Dns = 'dns';
    case Tls = 'tls';
    case WebsocketHandshake = 'websocket_handshake';
    case CoolifyAuth = 'coolify_auth';
    case CoolifyTimeout = 'coolify_timeout';
    case CoolifyError = 'coolify_error';
    case Configuration = 'configuration';
    case Unknown = 'unknown';
}
