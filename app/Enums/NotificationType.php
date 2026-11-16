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
            self::Info => 'Information',
            self::Success => 'Success',
            self::Warning => 'Warning',
            self::Alert => 'Notification',
            self::Order => 'Order',
            self::Community => 'Community',
            self::System => 'System',
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
