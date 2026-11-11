<?php

declare(strict_types=1);

/**
 * Build old→new Dutch key map for formal→informal address (u/uw → je/jouw).
 *
 * Usage: php database/scripts/build_informal_dutch_map.php
 */

$root = dirname(__DIR__, 2);
require $root.'/database/scripts/informal_dutch_lib.php';

$en = json_decode(file_get_contents($root.'/lang/en.json'), true, 512, JSON_THROW_ON_ERROR);

$map = [];
$unchanged = [];
$skipped = [];

foreach (array_keys($en) as $key) {
    if (! isFormalDutchAddress($key)) {
        continue;
    }

    if (shouldSkipInformalKey($key)) {
        $skipped[] = $key;

        continue;
    }

    $new = toInformalDutch($key);
    if ($new === $key) {
        $unchanged[] = $key;

        continue;
    }

    $map[$key] = $new;
}

$mapPath = $root.'/storage/app/informal-dutch-map.json';
file_put_contents(
    $mapPath,
    json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n"
);

$report = $root.'/storage/app/informal-dutch-map-report.txt';
file_put_contents($report, implode("\n", [
    'mapped: '.count($map),
    'unchanged_formal: '.count($unchanged),
    'skipped: '.count($skipped),
    '',
    '=== SKIPPED ===',
    ...$skipped,
    '',
    '=== UNCHANGED ===',
    ...$unchanged,
])."\n");

echo 'mapped '.count($map)." -> {$mapPath}\n";
echo 'unchanged '.count($unchanged).', skipped '.count($skipped)."\n";
