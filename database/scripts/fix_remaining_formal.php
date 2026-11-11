<?php

declare(strict_types=1);

/**
 * Second pass: convert leftover formal Dutch string literals in customer-facing code.
 * Also renames matching keys in lang/en.json + lang/fr.json when present.
 *
 * Usage: php database/scripts/fix_remaining_formal.php [--dry-run]
 */

$root = dirname(__DIR__, 2);
require $root.'/database/scripts/informal_dutch_lib.php';

$dryRun = in_array('--dry-run', $argv, true);

$dirs = [$root.'/app', $root.'/resources', $root.'/tests'];
$exts = ['php', 'tsx', 'ts', 'blade.php', 'js', 'mjs'];

$skipPathSubstrings = [
    DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR,
    '/admin/',
    'Journal.php',
    'informal_dutch',
    'build_informal',
    'apply_informal',
    'fix_remaining',
    'find_remaining',
    'check_',
    'sample_map',
    'inventory_formal',
    'patch_return_i18n',
    'patch_admin',
    'LegalPageSeeder.php',
    'FaqSeeder.php',
];

/** @var array<string, string> $map */
$map = [];

foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if (! $file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        foreach ($skipPathSubstrings as $skip) {
            if (str_contains($path, $skip)) {
                continue 2;
            }
        }
        $ok = false;
        foreach ($exts as $ext) {
            if (str_ends_with($path, '.'.$ext)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            continue;
        }

        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            continue;
        }

        if (! preg_match_all('/([\'"])((?:\\\\.|(?!\\1).)*)\\1/u', $contents, $matches)) {
            continue;
        }

        foreach ($matches[2] as $raw) {
            $s = stripcslashes($raw);
            if (mb_strlen($s) < 4 || mb_strlen($s) > 500) {
                continue;
            }
            if (! isFormalDutchAddress($s) || shouldSkipInformalKey($s)) {
                continue;
            }
            // Skip code identifiers / paths / unicode ranges / regex fragments
            if (preg_match('/^[A-Za-z0-9_\\\\.\\/-]+$/', $s)) {
                continue;
            }
            if (str_contains($s, 'U+') || str_contains($s, '\\u') || str_contains($s, '(?:') || str_contains($s, 'p{L}') || str_contains($s, 'p{N}')) {
                continue;
            }
            if (str_starts_with($s, '/') || str_starts_with($s, ')') || str_starts_with($s, "'")) {
                continue;
            }
            $new = toInformalDutch($s);
            if ($new === $s) {
                continue;
            }
            // Emphatic possession leftovers
            $new = preg_replace('/\bnu van je\b/u', 'nu van jou', $new) ?? $new;
            $new = preg_replace('/\bofficieel van je\b/u', 'officieel van jou', $new) ?? $new;
            $map[$s] = $new;
        }
    }
}

uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
echo 'found '.count($map)." leftover formal strings\n";

$enPath = $root.'/lang/en.json';
$frPath = $root.'/lang/fr.json';
$en = json_decode(file_get_contents($enPath), true, 512, JSON_THROW_ON_ERROR);
$fr = json_decode(file_get_contents($frPath), true, 512, JSON_THROW_ON_ERROR);
$enRenamed = 0;
$frRenamed = 0;

foreach ($map as $old => $new) {
    if (array_key_exists($old, $en)) {
        $val = $en[$old];
        unset($en[$old]);
        if (! array_key_exists($new, $en)) {
            $en[$new] = $val;
        }
        $enRenamed++;
    }
    if (array_key_exists($old, $fr)) {
        $val = $fr[$old];
        unset($fr[$old]);
        if (! array_key_exists($new, $fr)) {
            $fr[$new] = $val;
        }
        $frRenamed++;
    }
}

$fileHits = [];
$total = 0;

foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if (! $file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        foreach ($skipPathSubstrings as $skip) {
            if (str_contains($path, $skip)) {
                continue 2;
            }
        }
        $ok = false;
        foreach ($exts as $ext) {
            if (str_ends_with($path, '.'.$ext)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            continue;
        }

        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            continue;
        }

        $updated = $contents;
        $count = 0;
        foreach ($map as $old => $new) {
            if (mb_strlen($old) < 12) {
                foreach (['__', 't', 'trans'] as $fn) {
                    foreach (["'", '"', '`'] as $q) {
                        $needle = $fn.'('.$q.$old.$q;
                        if (str_contains($updated, $needle)) {
                            $n = substr_count($updated, $needle);
                            $updated = str_replace($needle, $fn.'('.$q.$new.$q, $updated);
                            $count += $n;
                        }
                    }
                }
                foreach (["'", '"'] as $q) {
                    $needle = $q.$old.$q;
                    if (str_contains($updated, $needle)) {
                        $n = substr_count($updated, $needle);
                        $updated = str_replace($needle, $q.$new.$q, $updated);
                        $count += $n;
                    }
                }

                continue;
            }

            if (! str_contains($updated, $old)) {
                continue;
            }
            $n = substr_count($updated, $old);
            $updated = str_replace($old, $new, $updated);
            $count += $n;
        }

        if ($count > 0) {
            $fileHits[$path] = $count;
            $total += $count;
            if (! $dryRun) {
                file_put_contents($path, $updated);
            }
        }
    }
}

if (! $dryRun) {
    ksort($en, SORT_STRING);
    ksort($fr, SORT_STRING);
    file_put_contents($enPath, json_encode($en, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    file_put_contents($frPath, json_encode($fr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
}

echo "en keys renamed: {$enRenamed}, fr: {$frRenamed}\n";
echo ($dryRun ? '[dry-run] ' : '').'files: '.count($fileHits).", replacements: {$total}\n";

file_put_contents(
    $root.'/storage/app/remaining-formal-map.json',
    json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n"
);
