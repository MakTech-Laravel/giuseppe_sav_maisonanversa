<?php

namespace App\Monitoring;

use App\Monitoring\Enums\MonitoredServiceId;
use InvalidArgumentException;

final class ServiceRegistry
{
    /**
     * @return array{label: string, coolify_uuid: string, coolify_resource_type: string, health_url?: string, health_path?: string, public_url?: string, host?: string, port?: int, scheme?: string, app_key?: string, internal_host?: string, internal_port?: int}
     */
    public function definition(MonitoredServiceId $service): array
    {
        $definitions = config('server_monitoring.services', []);

        if (! isset($definitions[$service->value]) || ! is_array($definitions[$service->value])) {
            throw new InvalidArgumentException("Unknown monitored service [{$service->value}].");
        }

        /** @var array{label: string, coolify_uuid: string, coolify_resource_type: string} $definition */
        $definition = $definitions[$service->value];

        return $definition;
    }

    public function resolve(string $logicalId): MonitoredServiceId
    {
        $service = MonitoredServiceId::tryFrom($logicalId);

        if ($service === null) {
            throw new InvalidArgumentException("Unknown logical service [{$logicalId}].");
        }

        $this->definition($service);

        return $service;
    }

    public function coolifyUuid(MonitoredServiceId $service): string
    {
        return (string) ($this->definition($service)['coolify_uuid'] ?? '');
    }

    public function coolifyResourceType(MonitoredServiceId $service): string
    {
        $type = strtolower((string) ($this->definition($service)['coolify_resource_type'] ?? 'application'));

        return in_array($type, ['application', 'service'], true) ? $type : 'application';
    }

    public function isCoolifyConfigured(MonitoredServiceId $service): bool
    {
        $base = (string) config('server_monitoring.coolify.base_url', '');
        $token = (string) config('server_monitoring.coolify.token', '');

        return $base !== '' && $token !== '' && $this->coolifyUuid($service) !== '';
    }

    public function mainAppHealthUrl(): string
    {
        $definition = $this->definition(MonitoredServiceId::MainApp);
        $base = rtrim((string) ($definition['health_url'] ?? config('app.url')), '/');
        $path = '/'.ltrim((string) ($definition['health_path'] ?? '/up'), '/');

        return $base.$path;
    }

    /**
     * @return list<string>
     */
    public function allowedProbeHosts(): array
    {
        $hosts = [];

        $main = parse_url($this->mainAppHealthUrl(), PHP_URL_HOST);
        if (is_string($main) && $main !== '') {
            $hosts[] = strtolower($main);
        }

        $reverbHost = (string) ($this->definition(MonitoredServiceId::Reverb)['host'] ?? '');
        if ($reverbHost !== '') {
            $hosts[] = strtolower($reverbHost);
        }

        $internal = (string) ($this->definition(MonitoredServiceId::Reverb)['internal_host'] ?? '');
        if ($internal !== '') {
            $hosts[] = strtolower($internal);
        }

        return array_values(array_unique($hosts));
    }
}
