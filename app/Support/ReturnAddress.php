<?php

namespace App\Support;

use App\Models\SiteSetting;

class ReturnAddress
{
    public static function resolve(): string
    {
        $fromSettings = trim((string) (SiteSetting::current()->return_address ?? ''));

        if ($fromSettings !== '') {
            return $fromSettings;
        }

        return (string) config('maison.returns.return_address');
    }
}
