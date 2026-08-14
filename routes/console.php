<?php

use App\Console\Commands\Editions\ReleaseExpiredHolds;
use App\Console\Commands\Mail\SendPostDeliveryFollowUps;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ReleaseExpiredHolds::class)->everyFiveMinutes();
Schedule::command(SendPostDeliveryFollowUps::class)->daily();
