<?php

use App\Support\FontPreloads;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Writes a manifest shaped like the Vite fonts plugin's, and returns its path.
 */
function fontManifest(array $families): string
{
    $path = storage_path('framework/testing/fonts-manifest-'.uniqid().'.json');

    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode(['version' => 1, 'families' => $families]));

    return $path;
}

function fontFiles(string $name, string $latinRange = 'U+0000-00FF,U+0131'): array
{
    return [
        ['file' => "assets/{$name}-cyrillic.woff2", 'format' => 'woff2', 'unicodeRange' => 'U+0400-045F'],
        ['file' => "assets/{$name}-latin.woff", 'format' => 'woff', 'unicodeRange' => $latinRange],
        ['file' => "assets/{$name}-latin.woff2", 'format' => 'woff2', 'unicodeRange' => $latinRange],
        ['file' => "assets/{$name}-latin-ext.woff2", 'format' => 'woff2', 'unicodeRange' => 'U+0100-02BA'],
    ];
}

beforeEach(function () {
    // A running dev server (public/hot) serves the fonts itself and nothing is preloaded.
    $this->withVite();
    Vite::useHotFile(storage_path('framework/testing/no-hot'));
});

test('nothing is preloaded while the dev server runs', function () {
    $hot = storage_path('framework/testing/hot');
    File::ensureDirectoryExists(dirname($hot));
    File::put($hot, 'http://127.0.0.1:5173');
    Vite::useHotFile($hot);

    $path = fontManifest(['montserrat' => ['variants' => ['300:normal' => ['files' => fontFiles('montserrat-300')]]]]);

    expect(FontPreloads::latin($path))->toBe([]);
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing'));
});

test('only the latin woff2 of baskervville 400 and montserrat 300 are preloaded', function () {
    $path = fontManifest([
        'baskervville' => ['variants' => [
            '400:normal' => ['files' => fontFiles('baskervville-400')],
            '400:italic' => ['files' => fontFiles('baskervville-400-italic')],
        ]],
        'montserrat' => ['variants' => [
            '300:normal' => ['files' => fontFiles('montserrat-300')],
            '500:normal' => ['files' => fontFiles('montserrat-500')],
        ]],
    ]);

    expect(FontPreloads::latin($path))->toBe([
        asset('build/assets/baskervville-400-latin.woff2'),
        asset('build/assets/montserrat-300-latin.woff2'),
    ]);
});

test('nothing is preloaded before a build', function () {
    expect(FontPreloads::latin(storage_path('framework/testing/missing.json')))->toBe([]);
});

test('a family or variant the manifest lacks is skipped', function () {
    $path = fontManifest([
        'montserrat' => ['variants' => ['300:normal' => ['files' => fontFiles('montserrat-300')]]],
    ]);

    expect(FontPreloads::latin($path))->toBe([asset('build/assets/montserrat-300-latin.woff2')]);
});
