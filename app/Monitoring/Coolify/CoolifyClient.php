<?php

namespace App\Monitoring\Coolify;

use App\Monitoring\Enums\ErrorCategory;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\ServiceRegistry;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

class CoolifyClient
{
    public function __construct(private ServiceRegistry $registry) {}

    public function isConfigured(): bool
    {
        $base = (string) config('server_monitoring.coolify.base_url', '');
        $token = (string) config('server_monitoring.coolify.token', '');

        return $base !== '' && $token !== '';
    }

    /**
     * @return array{ok: bool, status?: string, raw?: array<string, mixed>, error_category?: ErrorCategory, error_message?: string}
     */
    public function getResourceStatus(MonitoredServiceId $service): array
    {
        if (! $this->registry->isCoolifyConfigured($service)) {
            return [
                'ok' => false,
                'error_category' => ErrorCategory::Configuration,
                'error_message' => 'Coolify API is not configured for this service.',
            ];
        }

        $uuid = $this->registry->coolifyUuid($service);
        $type = $this->registry->coolifyResourceType($service);
        $path = $type === 'service' ? "/services/{$uuid}" : "/applications/{$uuid}";

        try {
            $response = $this->client()->get($path);

            if ($response->unauthorized() || $response->forbidden()) {
                return [
                    'ok' => false,
                    'error_category' => ErrorCategory::CoolifyAuth,
                    'error_message' => 'Coolify authentication failed.',
                ];
            }

            if ($response->failed()) {
                return [
                    'ok' => false,
                    'error_category' => ErrorCategory::CoolifyError,
                    'error_message' => 'Coolify status request failed with HTTP '.$response->status(),
                ];
            }

            /** @var array<string, mixed> $json */
            $json = $response->json() ?? [];

            return [
                'ok' => true,
                'status' => isset($json['status']) ? (string) $json['status'] : null,
                'raw' => $this->sanitizeResourcePayload($json),
            ];
        } catch (ConnectionException) {
            return [
                'ok' => false,
                'error_category' => ErrorCategory::CoolifyTimeout,
                'error_message' => 'Coolify API connection timed out.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'ok' => false,
                'error_category' => ErrorCategory::CoolifyError,
                'error_message' => 'Unexpected Coolify client error.',
            ];
        }
    }

    /**
     * @return array{ok: bool, deployment_uuid?: string|null, message?: string, error_category?: ErrorCategory, error_message?: string}
     */
    public function restart(MonitoredServiceId $service): array
    {
        if (! $this->registry->isCoolifyConfigured($service)) {
            return [
                'ok' => false,
                'error_category' => ErrorCategory::Configuration,
                'error_message' => 'Coolify API is not configured for this service.',
            ];
        }

        $uuid = $this->registry->coolifyUuid($service);
        $type = $this->registry->coolifyResourceType($service);
        $path = $type === 'service'
            ? "/services/{$uuid}/restart"
            : "/applications/{$uuid}/restart";

        try {
            $response = $this->client()->post($path);

            if ($response->unauthorized() || $response->forbidden()) {
                return [
                    'ok' => false,
                    'error_category' => ErrorCategory::CoolifyAuth,
                    'error_message' => 'Coolify authentication failed.',
                ];
            }

            if ($response->failed()) {
                return [
                    'ok' => false,
                    'error_category' => ErrorCategory::CoolifyError,
                    'error_message' => 'Coolify restart request failed with HTTP '.$response->status(),
                ];
            }

            /** @var array<string, mixed> $json */
            $json = $response->json() ?? [];

            return [
                'ok' => true,
                'deployment_uuid' => isset($json['deployment_uuid']) ? (string) $json['deployment_uuid'] : null,
                'message' => isset($json['message']) ? (string) $json['message'] : 'Restart request queued.',
            ];
        } catch (ConnectionException) {
            return [
                'ok' => false,
                'error_category' => ErrorCategory::CoolifyTimeout,
                'error_message' => 'Coolify API connection timed out.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'ok' => false,
                'error_category' => ErrorCategory::CoolifyError,
                'error_message' => 'Unexpected Coolify client error.',
            ];
        }
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl((string) config('server_monitoring.coolify.base_url'))
            ->withToken((string) config('server_monitoring.coolify.token'))
            ->acceptJson()
            ->timeout((int) config('server_monitoring.coolify.timeout', 15))
            ->connectTimeout((int) config('server_monitoring.coolify.connect_timeout', 5));
    }

    /**
     * Strip potentially sensitive fields from Coolify payloads before storage/UI.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizeResourcePayload(array $payload): array
    {
        $allowed = [
            'uuid', 'name', 'fqdn', 'status', 'ports_exposes', 'ports_mappings',
            'health_check_enabled', 'health_check_path', 'health_check_port',
            'health_check_interval', 'health_check_timeout', 'health_check_retries',
            'health_check_start_period', 'updated_at', 'created_at',
        ];

        $clean = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $clean[$key] = $payload[$key];
            }
        }

        return $clean;
    }
}
