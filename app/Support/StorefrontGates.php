<?php

namespace App\Support;

use App\Models\CommerceSetting;

class StorefrontGates
{
    /**
     * Purchases may start only when the env kill switch and Admin toggle are both on.
     */
    public static function purchasesEnabled(): bool
    {
        return (bool) config('maison.checkout.enabled')
            && CommerceSetting::current()->purchases_enabled;
    }

    /**
     * New sign-ups are allowed only when the env kill switch and Admin toggle are both on.
     */
    public static function registrationEnabled(): bool
    {
        return (bool) config('maison.registration.enabled')
            && CommerceSetting::current()->registration_enabled;
    }
}
