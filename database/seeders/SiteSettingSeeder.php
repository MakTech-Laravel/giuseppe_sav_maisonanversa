<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::query()->firstOrCreate([], [
            'phone' => '+32400000000',
            'whatsapp' => '32400000000',
            'email_hello' => 'hello@maisonanversa.com',
            'email_press' => 'press@maisonanversa.com',
            'instagram_url' => 'https://www.instagram.com/',
            'boutique_lat' => '51.2200000',
            'boutique_lng' => '4.4040000',
        ]);
    }
}
