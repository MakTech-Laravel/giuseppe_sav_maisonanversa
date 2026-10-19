<?php

namespace App\Monitoring\Enums;

enum MonitoredServiceId: string
{
    case MainApp = 'main-app';
    case Reverb = 'reverb';

    public function label(): string
    {
        return match ($this) {
            self::MainApp => 'Main Application',
            self::Reverb => 'Laravel Reverb',
        };
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Services actively probed and eligible for auto-recovery.
     * Main-app remains on the enum for registry/UI stubs but is not monitored.
     *
     * @return list<self>
     */
    public static function monitored(): array
    {
        return [self::Reverb];
    }
}
