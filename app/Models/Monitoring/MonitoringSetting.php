<?php

namespace App\Models\Monitoring;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MonitoringSetting extends Model
{
    protected $table = 'monitoring_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getBool(string $key, bool $default = false): bool
    {
        $raw = static::getValue($key);

        if ($raw === null) {
            return $default;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getValue(string $key): ?string
    {
        return Cache::remember("monitoring_setting:{$key}", 30, function () use ($key): ?string {
            $row = static::query()->where('key', $key)->first();

            return $row?->value;
        });
    }

    public static function put(string $key, string|bool|int|null $value): void
    {
        $stored = is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? '');

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $stored],
        );

        Cache::forget("monitoring_setting:{$key}");
    }
}
