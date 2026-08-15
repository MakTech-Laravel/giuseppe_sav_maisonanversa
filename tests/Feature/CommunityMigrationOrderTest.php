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

test('the translations unique index name fits mysql identifier length', function () {
    $source = file_get_contents(database_path(
        'migrations/2026_08_15_031856_create_translations_table.php',
    ));

    expect($source)->toContain("'translations_morph_locale_column_unique'");
    expect(strlen('translations_morph_locale_column_unique'))->toBeLessThanOrEqual(64);
});

test('composite unique indexes stay within mysql identifier length', function () {
    $source = file_get_contents(database_path(
        'migrations/2026_08_14_054455_create_community_session_participants_table.php',
    ));

    expect($source)->toContain("'csp_session_user_unique'");

    $default = 'community_session_participants_community_session_id_user_id_unique';

    expect(strlen($default))->toBeGreaterThan(64)
        ->and(strlen('csp_session_user_unique'))->toBeLessThanOrEqual(64);
});
