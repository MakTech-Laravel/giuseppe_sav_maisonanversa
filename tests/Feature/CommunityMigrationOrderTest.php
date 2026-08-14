<?php

/**
 * MySQL applies foreign keys as tables are created. Duplicate timestamps made
 * `community_comments` sort before `community_posts` (comments < posts), which
 * SQLite tolerated and MySQL rejected.
 */
test('community parent tables migrate before tables that reference them', function () {
    $files = collect(glob(database_path('migrations/*.php')))
        ->map(fn (string $path): string => basename($path))
        ->sort()
        ->values();

    $index = fn (string $needle): int => $files->search(
        fn (string $file): bool => str_contains($file, $needle),
    );

    expect($index('create_community_posts_table'))
        ->toBeLessThan($index('create_community_comments_table'))
        ->toBeLessThan($index('create_community_likes_table'))
        ->toBeLessThan($index('create_community_reports_table'));

    expect($index('create_community_sessions_table'))
        ->toBeLessThan($index('create_community_session_participants_table'));

    expect($index('create_community_events_table'))
        ->toBeLessThan($index('create_event_rsvps_table'));
});
