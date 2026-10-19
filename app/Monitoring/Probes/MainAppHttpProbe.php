<?php

namespace App\Monitoring\Probes;

use App\Monitoring\DTO\ProbeResult;
use App\Monitoring\Enums\ErrorCategory;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\ServiceRegistry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class MainAppHttpProbe
{
    public function __construct(private ServiceRegistry $registry) {}

    public function probe(): ProbeResult
    {
        $url = $this->registry->mainAppHealthUrl();
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return new ProbeResult(
                status: HealthStatus::Unknown,
                errorCategory: ErrorCategory::Configuration,
                errorMessage: 'Main application health URL is not configured.',
                details: ['url' => $url],
            );
        }

        if (! in_array(strtolower($host), $this->registry->allowedProbeHosts(), true)) {
            return new ProbeResult(
                status: HealthStatus::Unknown,
                errorCategory: ErrorCategory::Configuration,
                errorMessage: 'Health URL host is not in the allowlist.',
                details: ['host' => $host],
            );
        }

        $timeout = (int) config('server_monitoring.probe_timeout_seconds', 8);
        $started = hrtime(true);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(min(5, $timeout))
                ->withOptions(['allow_redirects' => false])
                ->get($url);

            $latency = (int) round((hrtime(true) - $started) / 1_000_000);
            $ok = $response->successful();

            return new ProbeResult(
                status: $ok ? HealthStatus::Healthy : HealthStatus::Unhealthy,
                latencyMs: $latency,
                httpStatus: $response->status(),
                errorCategory: $ok ? ErrorCategory::None : ErrorCategory::HttpFailure,
                errorMessage: $ok ? null : 'Health endpoint returned HTTP '.$response->status(),
                details: [
                    'url' => $url,
                    'body_preview' => str((string) $response->body())->limit(120)->toString(),
                ],
            );
        } catch (ConnectionException $e) {
            $latency = (int) round((hrtime(true) - $started) / 1_000_000);

            return new ProbeResult(
                status: HealthStatus::Unreachable,
                latencyMs: $latency,
                errorCategory: ErrorCategory::Timeout,
                errorMessage: 'Connection failed reaching main application health endpoint.',
                details: ['url' => $url, 'exception' => class_basename($e)],
            );
        } catch (Throwable $e) {
            report($e);
            $latency = (int) round((hrtime(true) - $started) / 1_000_000);

            return new ProbeResult(
                status: HealthStatus::Unhealthy,
                latencyMs: $latency,
                errorCategory: ErrorCategory::Unknown,
                errorMessage: 'Unexpected probe error.',
                details: ['url' => $url, 'exception' => class_basename($e)],
            );
        }
    }
}
