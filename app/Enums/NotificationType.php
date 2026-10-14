<?php

namespace App\Enums;

enum NotificationType: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Alert = 'alert';
    case Order = 'order';
    case Community = 'community';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Informatie',
            self::Success => 'Succes',
            self::Warning => 'Waarschuwing',
            self::Alert => 'Melding',
            self::Order => 'Bestelling',
            self::Community => 'Gemeenschap',
            self::System => 'Systeem',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
