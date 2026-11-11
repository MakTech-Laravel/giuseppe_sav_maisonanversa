<?php

declare(strict_types=1);

/**
 * Apply storage/app/informal-dutch-map.json:
 * 1) Rename keys in lang/en.json + lang/fr.json (keep values)
 * 2) Replace exact old key text in PHP/JS/TS/Blade call sites & tests
 *
 * Short keys (len < 12) are only rewritten inside t()/__() wrappers.
 *
 * Usage: php database/scripts/apply_informal_dutch_rename.php [--dry-run]
 */

$root = dirname(__DIR__, 2);
$dryRun = in_array('--dry-run', $argv, true);

$mapPath = $root.'/storage/app/informal-dutch-map.json';
/** @var array<string, string> $map */
$map = json_decode(file_get_contents($mapPath), true, 512, JSON_THROW_ON_ERROR);

uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

function loadLocale(string $path): array
{
    return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}

function saveLocale(string $path, array $data): void
{
    ksort($data, SORT_STRING);
    file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"
    );
}

/**
 * @param  array<string, string>  $map
 * @return array{0: array<string, string>, 1: int, 2: list<string>}
 */
function renameKeys(array $locale, array $map): array
{
    $missing = [];
    $renamed = 0;

    foreach ($map as $old => $new) {
        if (! array_key_exists($old, $locale)) {
            $missing[] = $old;

            continue;
        }

        if ($old === $new) {
            continue;
        }

        $value = $locale[$old];
        unset($locale[$old]);

        if (! array_key_exists($new, $locale)) {
            $locale[$new] = $value;
        }

        $renamed++;
    }

    return [$locale, $renamed, $missing];
}

/**
 * Replace a translation key only when wrapped in t()/__().
 */
function replaceWrappedKey(string $contents, string $old, string $new): array
{
    $count = 0;
    $fns = ['__', 't', 'trans'];
    $quotes = ["'", '"', '`'];

    foreach ($fns as $fn) {
        foreach ($quotes as $q) {
            $needle = $fn.'('.$q.$old.$q;
            $replacement = $fn.'('.$q.$new.$q;
            if (! str_contains($contents, $needle)) {
                continue;
            }
            $n = substr_count($contents, $needle);
            $contents = str_replace($needle, $replacement, $contents);
            $count += $n;

            // Also handle __($old) with optional whitespace: already exact.
            // Handle t($old, …) / __('…', []) — closing quote already in needle start only;
            // needle is fn('old so trailing args stay intact.
        }
    }

    // Blade @lang('…') / @lang("…")
    foreach ($quotes as $q) {
        $needle = '@lang('.$q.$old.$q;
        $replacement = '@lang('.$q.$new.$q;
        if (str_contains($contents, $needle)) {
            $n = substr_count($contents, $needle);
            $contents = str_replace($needle, $replacement, $contents);
            $count += $n;
        }
    }

    // PHPUnit / Pest expectations that quote the Dutch string directly
    foreach ($quotes as $q) {
        $needle = $q.$old.$q;
        if (! str_contains($contents, $needle)) {
            continue;
        }
        // Only for very short keys — too risky for medium keys in free text.
        // Handled by caller deciding wrap-only mode.
    }

    return [$contents, $count];
}

function replaceInFile(string $contents, array $map): array
{
    $total = 0;
    $updated = $contents;

    foreach ($map as $old => $new) {
        if ($old === $new) {
            continue;
        }

        $short = mb_strlen($old) < 12;

        if ($short) {
            [$updated, $count] = replaceWrappedKey($updated, $old, $new);
            $total += $count;

            // Also replace exact quoted occurrences in tests: expect(...'Uw'...)
            // when the entire quoted string equals the key.
            foreach (["'", '"'] as $q) {
                $needle = $q.$old.$q;
                if (! str_contains($updated, $needle)) {
                    continue;
                }
                $n = substr_count($updated, $needle);
                $updated = str_replace($needle, $q.$new.$q, $updated);
                $total += $n;
            }

            continue;
        }

        if (! str_contains($updated, $old)) {
            continue;
        }

        $occurrences = substr_count($updated, $old);
        $updated = str_replace($old, $new, $updated);
        $total += $occurrences;
    }

    return [$updated, $total];
}

$enPath = $root.'/lang/en.json';
$frPath = $root.'/lang/fr.json';
$en = loadLocale($enPath);
$fr = loadLocale($frPath);

[$en, $enRenamed, $enMissing] = renameKeys($en, $map);
[$fr, $frRenamed, $frMissing] = renameKeys($fr, $map);

echo "en renamed: {$enRenamed}, missing: ".count($enMissing)."\n";
echo "fr renamed: {$frRenamed}, missing: ".count($frMissing)."\n";

$extensions = ['php', 'tsx', 'ts', 'jsx', 'js', 'blade.php', 'mjs'];
$scanDirs = [
    $root.'/app',
    $root.'/resources',
    $root.'/tests',
    $root.'/database/scripts',
];

$skipBasenames = [
    'informal-dutch-map.json',
    'formal-dutch-keys.txt',
    'informal-dutch-map-report.txt',
    'apply_informal_dutch_rename.php',
    'build_informal_dutch_map.php',
    'inventory_formal_dutch.php',
    'sample_map_check.php',
    'check_short_keys.php',
    'en.json',
    'fr.json',
];

$fileHits = [];
$totalReplacements = 0;

foreach ($scanDirs as $dir) {
    if (! is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        if (! $file->isFile()) {
            continue;
        }
        if (in_array($file->getFilename(), $skipBasenames, true)) {
            continue;
        }
        $path = $file->getPathname();
        $ok = false;
        foreach ($extensions as $ext) {
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

        [$updated, $countForFile] = replaceInFile($contents, $map);
        if ($countForFile > 0) {
            $fileHits[$path] = $countForFile;
            $totalReplacements += $countForFile;
            if (! $dryRun) {
                file_put_contents($path, $updated);
            }
        }
    }
}

if (! $dryRun) {
    saveLocale($enPath, $en);
    saveLocale($frPath, $fr);
}

$reportPath = $root.'/storage/app/informal-dutch-apply-report.txt';
$lines = [
    'dry_run: '.($dryRun ? 'yes' : 'no'),
    'en_renamed: '.$enRenamed,
    'fr_renamed: '.$frRenamed,
    'files_touched: '.count($fileHits),
    'call_site_replacements: '.$totalReplacements,
    '',
    '=== FILES ===',
];
foreach ($fileHits as $path => $count) {
    $rel = str_replace($root.DIRECTORY_SEPARATOR, '', $path);
    $lines[] = $count."\t".$rel;
}
file_put_contents($reportPath, implode("\n", $lines)."\n");

echo ($dryRun ? '[dry-run] ' : '').'files touched: '.count($fileHits).", replacements: {$totalReplacements}\n";
echo "report: {$reportPath}\n";
