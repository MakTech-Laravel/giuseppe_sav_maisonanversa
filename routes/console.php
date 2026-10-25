<?php

use App\Console\Commands\Community\PruneSessionChatMessagesCommand;
use App\Console\Commands\Community\SendMatchCards;
use App\Console\Commands\Community\SendOpenSpotsAlerts;
use App\Console\Commands\Community\SendSessionChatDigestsCommand;
use App\Console\Commands\Community\SendSessionReminders;
use App\Console\Commands\Editions\ReleaseExpiredHolds;
use App\Console\Commands\FoundingCircle\PromoteRegisterInscriptionsCommand;
use App\Console\Commands\Mail\SendPostDeliveryFollowUps;
use App\Console\Commands\Monitoring\PruneMonitoringHistoryCommand;
use App\Console\Commands\Monitoring\RunHealthChecksCommand;
use App\Console\Commands\Returns\SendAdminReturnDigestCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ReleaseExpiredHolds::class)
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(SendPostDeliveryFollowUps::class)
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(SendAdminReturnDigestCommand::class)
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(PromoteRegisterInscriptionsCommand::class)
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(SendSessionReminders::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(SendOpenSpotsAlerts::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(SendMatchCards::class)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(PruneSessionChatMessagesCommand::class)
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(SendSessionChatDigestsCommand::class)
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('seo:generate')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(RunHealthChecksCommand::class)
    ->everyThirtySeconds()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command(PruneMonitoringHistoryCommand::class)
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();
