<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

test('scheduled commands prevent overlap and run on one server', function () {
    $events = collect(app(Schedule::class)->events());

    $required = [
        'editions:release-expired-holds',
        'mail:send-post-delivery-follow-ups',
        'seo:generate',
    ];

    foreach ($required as $signature) {
        /** @var Event|null $event */
        $event = $events->first(
            fn (Event $scheduled): bool => str_contains((string) $scheduled->command, $signature),
        );

        expect($event)->not->toBeNull("Missing schedule for {$signature}")
            ->and($event->withoutOverlapping)->toBeTrue()
            ->and($event->onOneServer)->toBeTrue();
    }
});
