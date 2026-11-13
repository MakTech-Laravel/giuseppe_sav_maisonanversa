<?php

declare(strict_types=1);

/**
 * Flip static UI i18n from Dutch keys to English keys using the Excel sheet.
 *
 * EN column = runtime key. NL/FR from Excel only — never invent.
 *
 * Usage:
 *   php database/scripts/flip_to_english_keys.php                 # inventory only
 *   php database/scripts/flip_to_english_keys.php --write-dicts   # nl.json + fr.json (matched)
 *   php database/scripts/flip_to_english_keys.php --apply-callsites [--dry-run]
 *   php database/scripts/flip_to_english_keys.php --delete-en-json
 */

$root = dirname(__DIR__, 2);

$writeDicts = in_array('--write-dicts', $argv, true);
$applyCallsites = in_array('--apply-callsites', $argv, true);
$deleteEnJson = in_array('--delete-en-json', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);

$xlsxPath = $root.'/Maison Anversa - UI translations EN-NL-FR.xlsx';
$enPath = $root.'/lang/en.json';
$frPath = $root.'/lang/fr.json';
$nlPath = $root.'/lang/nl.json';
$reportDir = $root.'/docs/translation-extract';
$inventoryPath = $reportDir.'/english-key-flip-inventory.md';
$leftoversPath = $reportDir.'/english-key-flip-leftovers.json';
$mapPath = $root.'/storage/app/english-key-dutch-to-en-map.json';
$matchedPath = $root.'/storage/app/english-key-matched-rows.json';
$applyReportPath = $root.'/storage/app/english-key-callsites-report.txt';

$fixedNames = [
    'Maison Anversa',
    'Heritage Nº 001',
    'Founding Edition',
    'Founding Circle',
    'Maison Archive',
    'Salon',
    'Bureau',
    'Le Cercle',
    'Galerie',
    'Salle Commune',
    'Bibliothèque',
    'Atelier',
    'Club Corner',
];
$fixedLookup = array_fill_keys($fixedNames, true);

/** @var array<string, string> $en */
$en = is_file($enPath)
    ? json_decode(file_get_contents($enPath), true, 512, JSON_THROW_ON_ERROR)
    : [];

$rows = loadExcelTranslationRows($xlsxPath);
echo 'excel data rows: '.count($rows).PHP_EOL;

/** @var array<string, list<string>> $byEn */
$byEn = [];
foreach ($en as $dutchKey => $english) {
    $byEn[$english][] = $dutchKey;
}

$matched = [];
$ambiguous = [];
$unmatched = [];
$skippedFixed = [];

foreach ($rows as $row) {
    $excelEn = trim((string) ($row['en'] ?? ''));
    $excelNl = trim((string) ($row['nl'] ?? ''));
    $excelFr = trim((string) ($row['fr'] ?? ''));

    if ($excelEn === '') {
        continue;
    }

    if (isset($fixedLookup[$excelEn]) || ($excelNl !== '' && isset($fixedLookup[$excelNl]))) {
        $skippedFixed[] = $row;

        continue;
    }

    // Brand-identical across columns
    if ($excelNl !== '' && $excelEn === $excelNl && ($excelFr === '' || $excelFr === $excelEn) && isset($fixedLookup[$excelEn])) {
        $skippedFixed[] = $row;

        continue;
    }

    $keys = $byEn[$excelEn] ?? [];

    if (count($keys) === 0) {
        // Secondary: Excel NL equals an existing Dutch key
        if ($excelNl !== '' && array_key_exists($excelNl, $en)) {
            $matched[] = [
                'old_dutch' => $excelNl,
                'en' => $excelEn,
                'nl' => $excelNl,
                'fr' => $excelFr,
                'method' => 'nl-key',
                'page' => $row['page'],
                'note' => $row['note'],
            ];

            continue;
        }

        $unmatched[] = $row;

        continue;
    }

    if (count($keys) > 1) {
        // Prefer NL if it disambiguates
        if ($excelNl !== '' && in_array($excelNl, $keys, true)) {
            $matched[] = [
                'old_dutch' => $excelNl,
                'en' => $excelEn,
                'nl' => $excelNl,
                'fr' => $excelFr,
                'method' => 'en-ambig-nl',
                'page' => $row['page'],
                'note' => $row['note'],
            ];

            continue;
        }

        $ambiguous[] = [
            'row' => $row,
            'keys' => $keys,
        ];

        continue;
    }

    $matched[] = [
        'old_dutch' => $keys[0],
        'en' => $excelEn,
        'nl' => $excelNl !== '' ? $excelNl : $keys[0],
        'fr' => $excelFr,
        'method' => 'en-unique',
        'page' => $row['page'],
        'note' => $row['note'],
    ];
}

if (! is_dir($reportDir)) {
    mkdir($reportDir, 0775, true);
}

if (! is_dir($root.'/storage/app')) {
    mkdir($root.'/storage/app', 0775, true);
}

writeInventoryReport($inventoryPath, $matched, $ambiguous, $unmatched, $skippedFixed, count($en));

$dutchToEn = [];
foreach ($matched as $m) {
    $dutch = $m['old_dutch'];
    $english = $m['en'];
    if ($dutch === '' || $english === '') {
        continue;
    }
    // First wins; collisions reported separately below
    if (! isset($dutchToEn[$dutch])) {
        $dutchToEn[$dutch] = $english;
    }
}

// Inventory always refreshes matched/leftover reports. The dutch→en call-site
 // map is only rewritten here during a plain inventory run so --apply-callsites
 // does not clobber the fuller map produced by --write-dicts.
if (! $writeDicts && ! $applyCallsites && ! $deleteEnJson) {
    file_put_contents(
        $mapPath,
        json_encode($dutchToEn, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"
    );
}
file_put_contents(
    $matchedPath,
    json_encode($matched, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"
);
file_put_contents(
    $leftoversPath,
    json_encode([
        'ambiguous' => $ambiguous,
        'unmatched' => $unmatched,
        'skipped_fixed' => $skippedFixed,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"
);

echo "inventory: {$inventoryPath}".PHP_EOL;
echo 'matched='.count($matched)
    .' map_keys='.count($dutchToEn)
    .' ambiguous='.count($ambiguous)
    .' unmatched='.count($unmatched)
    .' skipped_fixed='.count($skippedFixed).PHP_EOL;

if ($writeDicts) {
    /** @var array<string, string> $oldFr */
    $oldFr = is_file($frPath)
        ? json_decode(file_get_contents($frPath), true, 512, JSON_THROW_ON_ERROR)
        : [];

    $nl = [];
    $fr = [];

    // 1) Structural remap: English value becomes key for EVERY former Dutch key.
    //    Shared EN values still map every Dutch variant → same English key.
    //    NL/FR pick the first Dutch key's pair; Excel overlay wins next.
    foreach ($byEn as $english => $dutchKeys) {
        if ($english === '') {
            continue;
        }
        foreach ($dutchKeys as $dutchKey) {
            if (! isset($nl[$english])) {
                $nl[$english] = $dutchKey;
            }
            if (! isset($fr[$english]) && isset($oldFr[$dutchKey]) && $oldFr[$dutchKey] !== '') {
                $fr[$english] = $oldFr[$dutchKey];
            }
        }
    }

    // 2) Excel matched rows override (authoritative NL/FR from sheet).
    foreach ($matched as $m) {
        $key = $m['en'];
        if ($key === '') {
            continue;
        }
        if ($m['nl'] !== '') {
            $nl[$key] = $m['nl'];
        }
        if ($m['fr'] !== '') {
            $fr[$key] = $m['fr'];
        }
    }

    // 3) Do not insert fixed brand / allowlisted terms — ShellTranslationKeysTest
    //    expects them omitted so i18next falls back to the English key itself.
    $brandOmit = [
        'Club Corner',
        'Founding Circle',
        'Founding Edition',
        'Heritage Nº 001',
        'Journal',
        ...$fixedNames,
    ];
    foreach (array_unique($brandOmit) as $name) {
        unset($nl[$name], $fr[$name]);
    }

    saveLocale($nlPath, $nl);
    saveLocale($frPath, $fr);

    // Full dutch→en map for call sites (all former keys, including shared EN).
    $fullMap = [];
    foreach ($byEn as $english => $dutchKeys) {
        if ($english === '') {
            continue;
        }
        foreach ($dutchKeys as $dutchKey) {
            $fullMap[$dutchKey] = $english;
        }
    }
    foreach ($matched as $m) {
        if ($m['old_dutch'] !== '' && $m['en'] !== '') {
            $fullMap[$m['old_dutch']] = $m['en'];
        }
    }
    // Prefer Excel NL as the Dutch source when present for reverse lookups later.
    file_put_contents(
        $mapPath,
        json_encode($fullMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"
    );

    echo 'wrote nl.json keys='.count($nl).' fr.json keys='.count($fr)
        .' full_map='.count($fullMap).PHP_EOL;
}

if ($applyCallsites) {
    if (! is_file($mapPath)) {
        fwrite(STDERR, "Missing map {$mapPath}\n");
        exit(1);
    }

    /** @var array<string, string> $map */
    $map = json_decode(file_get_contents($mapPath), true, 512, JSON_THROW_ON_ERROR);
    uksort($map, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

    $fileHits = replaceCallSites($root, $map, $dryRun);
    $total = array_sum($fileHits);

    $lines = [
        'dry_run: '.($dryRun ? 'yes' : 'no'),
        'map_size: '.count($map),
        'files_touched: '.count($fileHits),
        'replacements: '.$total,
        '',
        '=== FILES ===',
    ];
    foreach ($fileHits as $path => $count) {
        $lines[] = $count."\t".str_replace($root.DIRECTORY_SEPARATOR, '', $path);
    }
    file_put_contents($applyReportPath, implode("\n", $lines)."\n");

    echo ($dryRun ? '[dry-run] ' : '').'files='.count($fileHits)." replacements={$total}".PHP_EOL;
    echo "report: {$applyReportPath}".PHP_EOL;
}

if ($deleteEnJson) {
    if (is_file($enPath)) {
        if ($dryRun) {
            echo "[dry-run] would delete {$enPath}".PHP_EOL;
        } else {
            unlink($enPath);
            echo "deleted {$enPath}".PHP_EOL;
        }
    }
}

/**
 * @return list<array{page: ?string, n: ?string, en: ?string, nl: ?string, fr: ?string, note: ?string}>
 */
function loadExcelTranslationRows(string $xlsxPath): array
{
    $zip = new ZipArchive;
    if ($zip->open($xlsxPath) !== true) {
        throw new RuntimeException('Cannot open xlsx: '.$xlsxPath);
    }

    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($xml === false) {
        throw new RuntimeException('Missing sheet1.xml in xlsx');
    }

    preg_match_all('/<row r="(\d+)">(.*?)<\/row>/s', $xml, $matches, PREG_SET_ORDER);
    $rows = [];

    foreach ($matches as $m) {
        $r = (int) $m[1];
        if ($r === 1) {
            continue;
        }

        $chunk = $m[2];
        $cells = [];
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            if (! preg_match('/<c r="'.$col.$r.'"[^>]*>(.*?)<\/c>/s', $chunk, $cm)) {
                $cells[$col] = null;

                continue;
            }
            if (preg_match('/<t[^>]*>(.*?)<\/t>/s', $cm[1], $tm)) {
                $cells[$col] = html_entity_decode($tm[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
            } elseif (preg_match('/<v>(.*?)<\/v>/s', $cm[1], $vm)) {
                $cells[$col] = $vm[1];
            } else {
                $cells[$col] = null;
            }
        }

        if ($cells['C'] === null || $cells['C'] === '') {
            continue;
        }

        $rows[] = [
            'page' => $cells['A'],
            'n' => $cells['B'],
            'en' => $cells['C'],
            'nl' => $cells['D'],
            'fr' => $cells['E'],
            'note' => $cells['F'],
        ];
    }

    return $rows;
}

/**
 * @param  array<string, string>  $data
 */
function saveLocale(string $path, array $data): void
{
    ksort($data, SORT_STRING);
    file_put_contents(
        $path,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n"
    );
}

/**
 * @param  list<array<string, mixed>>  $matched
 * @param  list<array<string, mixed>>  $ambiguous
 * @param  list<array<string, mixed>>  $unmatched
 * @param  list<array<string, mixed>>  $skippedFixed
 */
function writeInventoryReport(
    string $path,
    array $matched,
    array $ambiguous,
    array $unmatched,
    array $skippedFixed,
    int $currentEnKeys,
): void {
    $methods = [];
    foreach ($matched as $m) {
        $methods[$m['method']] = ($methods[$m['method']] ?? 0) + 1;
    }

    $lines = [
        '# English-key flip inventory',
        '',
        'Generated by `database/scripts/flip_to_english_keys.php` (inventory).',
        '',
        '## Summary',
        '',
        '- Current Dutch keys in en.json: **'.$currentEnKeys.'**',
        '- Matched Excel rows: **'.count($matched).'**',
        '- Ambiguous EN: **'.count($ambiguous).'**',
        '- Unmatched EN: **'.count($unmatched).'**',
        '- Skipped fixed/brand: **'.count($skippedFixed).'**',
        '',
        '### Match methods',
        '',
    ];

    foreach ($methods as $method => $count) {
        $lines[] = '- `'.$method.'`: '.$count;
    }

    $lines[] = '';
    $lines[] = '## Matched sample (40)';
    $lines[] = '';
    foreach (array_slice($matched, 0, 40) as $m) {
        $lines[] = '- `'.$m['old_dutch'].'` → EN `'.$m['en'].'` | NL `'.$m['nl'].'` | FR `'.$m['fr'].'` ('.$m['method'].')';
    }

    $lines[] = '';
    $lines[] = '## Ambiguous EN';
    $lines[] = '';
    foreach ($ambiguous as $item) {
        $row = $item['row'];
        $lines[] = '- EN `'.$row['en'].'` → keys: '.implode(' | ', array_map(static fn ($k) => '`'.$k.'`', $item['keys']));
    }

    $lines[] = '';
    $lines[] = '## Unmatched EN';
    $lines[] = '';
    foreach ($unmatched as $row) {
        $lines[] = '- EN `'.$row['en'].'` | NL `'.$row['nl'].'` | FR `'.$row['fr'].'`'
            .($row['note'] ? ' | note: '.$row['note'] : '');
    }

    $lines[] = '';
    $lines[] = '## Skipped fixed/brand';
    $lines[] = '';
    foreach ($skippedFixed as $row) {
        $lines[] = '- EN `'.$row['en'].'`';
    }
    $lines[] = '';

    file_put_contents($path, implode("\n", $lines));
}

/**
 * @param  array<string, string>  $map
 * @return array<string, int>
 */
function replaceCallSites(string $root, array $map, bool $dryRun): array
{
    $extensions = ['php', 'tsx', 'ts', 'jsx', 'js', 'blade.php', 'mjs'];
    $scanDirs = [
        $root.'/app',
        $root.'/resources',
        $root.'/tests',
        $root.'/database/scripts',
    ];
    $skipBasenames = [
        'en.json',
        'fr.json',
        'nl.json',
        'english-key-dutch-to-en-map.json',
        'english-key-matched-rows.json',
        'english-key-callsites-report.txt',
        'english-key-flip-inventory.md',
        'english-key-flip-leftovers.json',
        'flip_to_english_keys.php',
        'apply_informal_dutch_rename.php',
        'informal-dutch-map.json',
    ];

    $oldKeys = array_keys($map);
    $fileHits = [];

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

            // Skip node_modules / vendor under resources if any
            if (str_contains($path, DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $contents = file_get_contents($path);
            if ($contents === false || $contents === '') {
                continue;
            }

            [$updated, $countForFile] = replaceInFile($contents, $map, $oldKeys);
            if ($countForFile > 0) {
                $fileHits[$path] = $countForFile;
                if (! $dryRun) {
                    file_put_contents($path, $updated);
                }
            }
        }
    }

    return $fileHits;
}

/**
 * @param  array<string, string>  $map
 * @param  list<string>  $oldKeys
 * @return array{0: string, 1: int}
 */
function replaceInFile(string $contents, array $map, array $oldKeys): array
{
    $total = 0;
    $updated = $contents;

    foreach ($map as $old => $new) {
        if ($old === $new) {
            continue;
        }

        $isSubstringOfOther = false;
        foreach ($oldKeys as $other) {
            if ($other !== $old && str_contains($other, $old)) {
                $isSubstringOfOther = true;
                break;
            }
        }

        $short = mb_strlen($old) < 12 || $isSubstringOfOther;

        if ($short) {
            [$updated, $count] = replaceWrappedKey($updated, $old, $new);
            $total += $count;

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

/**
 * @return array{0: string, 1: int}
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
        }
    }

    foreach ($quotes as $q) {
        $needle = '@lang('.$q.$old.$q;
        $replacement = '@lang('.$q.$new.$q;
        if (str_contains($contents, $needle)) {
            $n = substr_count($contents, $needle);
            $contents = str_replace($needle, $replacement, $contents);
            $count += $n;
        }
    }

    return [$contents, $count];
}
