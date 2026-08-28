<?php

namespace App\Services\Inquiry;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as CookieContract;

class InquiryDeviceCookie
{
    public const NAME = 'maison_inquiry_device';

    public function remember(Request $request): string
    {
        $token = $this->token($request) ?? $this->mint();

        Cookie::queue($this->cookie($token));

        return $token;
    }

    public function token(Request $request): ?string
    {
        $existing = $request->cookie(self::NAME);

        if (is_string($existing) && Str::isUuid($existing)) {
            return $existing;
        }

        return null;
    }

    public function cookie(string $token): CookieContract
    {
        return cookie(
            name: self::NAME,
            value: $token,
            minutes: 60 * 24 * 365,
            path: '/',
            secure: config('session.secure'),
            httpOnly: true,
            raw: false,
            sameSite: config('session.same_site', 'lax'),
        );
    }

    public function mint(): string
    {
        return (string) Str::uuid();
    }
}
