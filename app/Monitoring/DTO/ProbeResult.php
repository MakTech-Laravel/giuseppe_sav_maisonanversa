<?php

namespace App\Monitoring\DTO;

use App\Monitoring\Enums\ErrorCategory;
use App\Monitoring\Enums\HealthStatus;

final readonly class ProbeResult
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public HealthStatus $status,
        public ?int $latencyMs = null,
        public ?int $httpStatus = null,
        public ?bool $websocketOk = null,
        public ErrorCategory $errorCategory = ErrorCategory::None,
        public ?string $errorMessage = null,
        public array $details = [],
    ) {}

    public function isHealthy(): bool
    {
        return $this->status === HealthStatus::Healthy;
    }
}
