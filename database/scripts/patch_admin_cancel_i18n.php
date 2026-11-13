<?php

declare(strict_types=1);

$patches = [
    'lang/en.json' => [
        'Cancelled by administrator.' => 'Cancelled by administrator.',
    ],
    'lang/fr.json' => [
        'Cancelled by administrator.' => 'Annulé par l’administrateur.',
    ],
];

foreach ($patches as $path => $patch) {
    $full = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    /** @var array<string, string> $data */
    $data = json_decode((string) file_get_contents($full), true, 512, JSON_THROW_ON_ERROR);
    $added = 0;

    foreach ($patch as $key => $value) {
        if (! array_key_exists($key, $data)) {
            $data[$key] = $value;
            $added++;
        }
    }

    ksort($data);
    file_put_contents(
        $full,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
    );
    echo basename($path)." +{$added}\n";
}
