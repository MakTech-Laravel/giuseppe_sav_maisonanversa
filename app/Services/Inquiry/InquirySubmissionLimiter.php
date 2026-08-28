<?php

namespace App\Services\Inquiry;

use App\Enums\InquiryType;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InquirySubmissionLimiter
{
    public const MAX_PER_KIND = 2;

    public const WINDOW_HOURS = 24;

    public function __construct(private InquiryDeviceCookie $deviceCookie) {}

    public function tooMany(Request $request, InquiryType $type): bool
    {
        return $this->count($request, $type) >= self::MAX_PER_KIND;
    }

    public function count(Request $request, InquiryType $type): int
    {
        $ip = $request->ip();
        $device = $this->deviceCookie->token($request);
        $userId = $request->user()?->id;

        if (! filled($ip) && $device === null && $userId === null) {
            return self::MAX_PER_KIND;
        }

        return Inquiry::query()
            ->where('type', $type)
            ->where('created_at', '>=', now()->subHours(self::WINDOW_HOURS))
            ->where(function (Builder $query) use ($ip, $device, $userId): void {
                if (filled($ip)) {
                    $query->orWhere('ip', $ip);
                }

                if ($device !== null) {
                    $query->orWhere('device_token', $device);
                }

                if ($userId !== null) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->count();
    }
}
