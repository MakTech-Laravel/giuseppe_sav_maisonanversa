<?php

namespace Database\Seeders;

use App\Models\CommerceSetting;
use Illuminate\Database\Seeder;

class CommerceSettingSeeder extends Seeder
{
    /**
     * Seed display-only shipping and delivery defaults (not Stripe line items).
     */
    public function run(): void
    {
        CommerceSetting::current();
    }
}
