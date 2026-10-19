<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Coolify API
    |--------------------------------------------------------------------------
    */
    'coolify' => [
        'base_url' => rtrim((string) env('COOLIFY_API_URL', ''), '/'),
        'token' => (string) env('COOLIFY_API_TOKEN', ''),
        'timeout' => (int) env('COOLIFY_API_TIMEOUT', 15),
        'connect_timeout' => (int) env('COOLIFY_API_CONNECT_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Probe / recovery policy
    |--------------------------------------------------------------------------
    */
    'check_interval_seconds' => (int) env('MONITORING_CHECK_INTERVAL_SECONDS', 30),
    'failure_threshold' => (int) env('MONITORING_FAILURE_THRESHOLD', 3),
    'startup_grace_seconds' => (int) env('MONITORING_STARTUP_GRACE_SECONDS', 90),
    'restart_cooldown_seconds' => (int) env('MONITORING_RESTART_COOLDOWN_SECONDS', 120),
    'auto_recovery_window_seconds' => (int) env('MONITORING_AUTO_RECOVERY_WINDOW_SECONDS', 600),
    'auto_recovery_max_attempts' => (int) env('MONITORING_AUTO_RECOVERY_MAX_ATTEMPTS', 2),
    'auto_recovery_enabled_default' => (bool) env('MONITORING_AUTO_RECOVERY_ENABLED', false),
    'post_restart_verify_delay_seconds' => (int) env('MONITORING_POST_RESTART_VERIFY_DELAY_SECONDS', 15),
    'probe_timeout_seconds' => (int) env('MONITORING_PROBE_TIMEOUT_SECONDS', 8),
    /*
    | Verify TLS certificates for HTTPS/WSS probes. Keep true in production.
    | Set MONITORING_TLS_VERIFY=false only when the Reverb host uses an untrusted
    | or incomplete certificate chain (common with some Coolify/local setups).
    */
    'tls_verify' => filter_var(env('MONITORING_TLS_VERIFY', true), FILTER_VALIDATE_BOOLEAN),
    'history_retention_days' => (int) env('MONITORING_HISTORY_RETENTION_DAYS', 30),
    'notification_cooldown_seconds' => (int) env('MONITORING_NOTIFICATION_COOLDOWN_SECONDS', 300),
    'stale_after_seconds' => (int) env('MONITORING_STALE_AFTER_SECONDS', 120),

    /*
    |--------------------------------------------------------------------------
    | Logical service registry (never accept UUIDs from the browser)
    |--------------------------------------------------------------------------
    */
    'services' => [
        'main-app' => [
            'label' => 'Main Application',
            'coolify_uuid' => (string) env('COOLIFY_MAIN_APP_UUID', ''),
            'coolify_resource_type' => (string) env('COOLIFY_MAIN_APP_RESOURCE_TYPE', 'application'),
            'health_url' => (string) env('MONITORING_MAIN_APP_URL', env('APP_URL', 'http://localhost')),
            'health_path' => (string) env('MONITORING_MAIN_HEALTH_PATH', '/up'),
            'public_url' => (string) env('MONITORING_MAIN_APP_URL', env('APP_URL', 'http://localhost')),
        ],
        'reverb' => [
            'label' => 'Laravel Reverb',
            'coolify_uuid' => (string) env('COOLIFY_REVERB_UUID', ''),
            'coolify_resource_type' => (string) env('COOLIFY_REVERB_RESOURCE_TYPE', 'application'),
            'host' => (string) env('REVERB_HOST', ''),
            'port' => (int) env('REVERB_PORT', 443),
            'scheme' => (string) env('REVERB_SCHEME', 'https'),
            'app_key' => (string) env('REVERB_APP_KEY', ''),
            'internal_host' => (string) env('MONITORING_REVERB_INTERNAL_HOST', ''),
            'internal_port' => (int) env('MONITORING_REVERB_INTERNAL_PORT', 8080),
        ],
    ],

];
