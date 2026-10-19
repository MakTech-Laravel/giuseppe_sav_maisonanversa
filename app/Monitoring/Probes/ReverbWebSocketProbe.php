<?php

namespace App\Monitoring\Probes;

use App\Monitoring\DTO\ProbeResult;
use App\Monitoring\Enums\ErrorCategory;
use App\Monitoring\Enums\HealthStatus;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\ServiceRegistry;
use Throwable;

/**
 * Protocol-level WebSocket upgrade check against the public Reverb/Pusher endpoint.
 * Does not rely on a generic HTTP GET.
 */
class ReverbWebSocketProbe
{
    public function __construct(private ServiceRegistry $registry) {}

    public function probe(): ProbeResult
    {
        $definition = $this->registry->definition(MonitoredServiceId::Reverb);
        $host = trim((string) ($definition['host'] ?? ''));
        $port = (int) ($definition['port'] ?? 443);
        $scheme = strtolower((string) ($definition['scheme'] ?? 'https'));
        $appKey = trim((string) ($definition['app_key'] ?? ''));

        if ($host === '' || $appKey === '') {
            return new ProbeResult(
                status: HealthStatus::Unknown,
                errorCategory: ErrorCategory::Configuration,
                errorMessage: 'REVERB_HOST or REVERB_APP_KEY is not configured.',
                details: [
                    'host' => $host !== '' ? $host : null,
                    'port' => $port,
                    'scheme' => $scheme,
                    'app_key_configured' => $appKey !== '',
                ],
            );
        }

        if (! in_array(strtolower($host), $this->registry->allowedProbeHosts(), true)) {
            return new ProbeResult(
                status: HealthStatus::Unknown,
                errorCategory: ErrorCategory::Configuration,
                errorMessage: 'Reverb host is not in the allowlist.',
                details: ['host' => $host],
            );
        }

        $timeout = (float) config('server_monitoring.probe_timeout_seconds', 8);
        $verifyTls = (bool) config('server_monitoring.tls_verify', true);
        $useTls = in_array($scheme, ['https', 'wss'], true);
        $path = '/app/'.rawurlencode($appKey).'?protocol=7&client=php&version=7.0.0&flash=false';
        $key = base64_encode(random_bytes(16));
        $remote = ($useTls ? 'ssl://' : '').$host.':'.$port;

        $started = hrtime(true);
        $socket = null;

        try {
            while (openssl_error_string() !== false) {
                // Drain stale OpenSSL errors from earlier requests.
            }

            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => $useTls && $verifyTls,
                    'verify_peer_name' => $useTls && $verifyTls,
                    'peer_name' => $host,
                    'SNI_enabled' => true,
                    'capture_peer_cert' => true,
                ],
            ]);

            $socket = @stream_socket_client(
                $remote,
                $errno,
                $errstr,
                $timeout,
                STREAM_CLIENT_CONNECT,
                $context,
            );

            if ($socket === false) {
                $latency = (int) round((hrtime(true) - $started) / 1_000_000);
                $opensslErrors = $this->collectOpenSslErrors();
                $combined = strtolower(trim($errstr.' '.implode(' ', $opensslErrors)));
                $isTls = $useTls && (
                    $opensslErrors !== []
                    || str_contains($combined, 'ssl')
                    || str_contains($combined, 'tls')
                    || str_contains($combined, 'certificate')
                    || str_contains($combined, 'cert')
                    || ($errno === 0 && $errstr === '')
                );

                return new ProbeResult(
                    status: HealthStatus::Unreachable,
                    latencyMs: $latency,
                    websocketOk: false,
                    errorCategory: $isTls ? ErrorCategory::Tls : ErrorCategory::Timeout,
                    errorMessage: $isTls
                        ? 'TLS connection to Reverb failed (untrusted or incomplete certificate). Fix the Coolify/WS certificate, or set MONITORING_TLS_VERIFY=false for local diagnostics only.'
                        : 'Unable to connect to Reverb WebSocket endpoint.',
                    details: [
                        'host' => $host,
                        'port' => $port,
                        'scheme' => $scheme,
                        'errno' => $errno,
                        'errstr' => $errstr !== '' ? $errstr : null,
                        'openssl' => $opensslErrors === [] ? null : $opensslErrors,
                        'tls_verify' => $verifyTls,
                    ],
                );
            }

            stream_set_timeout($socket, (int) max(1, ceil($timeout)));

            $request = "GET {$path} HTTP/1.1\r\n"
                ."Host: {$host}\r\n"
                ."Upgrade: websocket\r\n"
                ."Connection: Upgrade\r\n"
                ."Sec-WebSocket-Key: {$key}\r\n"
                ."Sec-WebSocket-Version: 13\r\n"
                ."Sec-WebSocket-Protocol: pusher\r\n"
                ."\r\n";

            fwrite($socket, $request);

            $response = $this->readHttpHeaders($socket);
            $latency = (int) round((hrtime(true) - $started) / 1_000_000);

            $ok = str_contains($response, '101')
                && (str_contains(strtolower($response), 'upgrade: websocket')
                    || str_contains(strtolower($response), 'switching protocols'));

            if ($ok) {
                return new ProbeResult(
                    status: HealthStatus::Healthy,
                    latencyMs: $latency,
                    httpStatus: 101,
                    websocketOk: true,
                    details: [
                        'host' => $host,
                        'port' => $port,
                        'scheme' => $scheme,
                        'path' => '/app/{key}',
                        'handshake' => 'switching_protocols',
                        'tls_verify' => $verifyTls,
                    ],
                );
            }

            $statusLine = strtok($response, "\r\n") ?: 'empty response';
            $httpStatus = $this->extractStatusCode($response);

            $message = match ($httpStatus) {
                503 => 'Reverb proxy returned 503 Service Unavailable — the Coolify Reverb container is likely stopped or crashing.',
                404 => 'Reverb WebSocket path not found (404). Check reverse-proxy routing to /app/{key}.',
                401, 403 => 'Reverb rejected the WebSocket upgrade (auth/key mismatch).',
                default => 'WebSocket handshake failed'.($httpStatus ? " (HTTP {$httpStatus})." : '.'),
            };

            return new ProbeResult(
                status: HealthStatus::Unhealthy,
                latencyMs: $latency,
                httpStatus: $httpStatus,
                websocketOk: false,
                errorCategory: ErrorCategory::WebsocketHandshake,
                errorMessage: $message,
                details: [
                    'host' => $host,
                    'port' => $port,
                    'status_line' => str($statusLine)->limit(160)->toString(),
                    'tls_verify' => $verifyTls,
                ],
            );
        } catch (Throwable $e) {
            report($e);
            $latency = (int) round((hrtime(true) - $started) / 1_000_000);

            return new ProbeResult(
                status: HealthStatus::Unreachable,
                latencyMs: $latency,
                websocketOk: false,
                errorCategory: ErrorCategory::Unknown,
                errorMessage: 'Unexpected WebSocket probe error.',
                details: [
                    'host' => $host,
                    'exception' => class_basename($e),
                ],
            );
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
        }
    }

    /**
     * Read only the HTTP response headers so we do not block waiting for a WS frame body.
     */
    private function readHttpHeaders($socket): string
    {
        $response = '';

        while (! str_contains($response, "\r\n\r\n")) {
            $chunk = fread($socket, 1024);

            if ($chunk === false || $chunk === '') {
                break;
            }

            $response .= $chunk;

            if (strlen($response) > 8192) {
                break;
            }
        }

        return $response;
    }

    /**
     * @return list<string>
     */
    private function collectOpenSslErrors(): array
    {
        $errors = [];

        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }

        return array_values(array_unique($errors));
    }

    private function extractStatusCode(string $response): ?int
    {
        if (preg_match('/^HTTP\/\d\.\d\s+(\d{3})/i', $response, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
