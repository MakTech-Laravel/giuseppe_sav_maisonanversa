<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Reports which site photographs are actually on disk under public/images.
 *
 * The front end holds a manifest of every image the design expects; this class
 * answers which of them exist yet. Anything absent renders as a brand-palette
 * placeholder instead of a broken image, so dropping real photography in is a
 * file copy with no code change.
 */
final class Imagery
{
    private const DIRECTORY = 'images';

    private const EXTENSIONS = ['avif', 'jpeg', 'jpg', 'png', 'svg', 'webp'];

    /**
     * Maps imagery asset keys to their public paths. Mirrors
     * `resources/js/lib/imagery.ts` so admin previews can resolve seeded
     * gallery keys such as `heritage-001-front`.
     *
     * @var array<string, string>
     */
    private const ASSET_PATHS = [
        'room-entrance' => 'images/rooms/room-entrance.png',
        'room-library' => 'images/rooms/room-library.png',
        'room-atelier' => 'images/rooms/room-atelier.png',
        'room-dressing' => 'images/rooms/room-dressing.png',
        'room-coffee' => 'images/rooms/room-coffee.png',
        'room-courtyard' => 'images/rooms/room-courtyard.png',
        'hero-mansion' => 'images/brand/hero-mansion.png',
        'maison-facade' => 'images/brand/maison-facade.png',
        'maison-facade-house' => 'images/brand/maison-facade-house.png',
        'antwerp-cityscape' => 'images/brand/antwerp-cityscape.png',
        'heritage-001-front' => 'images/product/heritage-001-front.png',
        'heritage-001-detail-gravure' => 'images/product/heritage-001-detail-gravure.png',
        'heritage-001-lifestyle-court' => 'images/product/heritage-001-lifestyle-court.png',
        'atelier-workshop' => 'images/product/atelier-workshop.png',
        'antwerp-ets-band' => 'images/editorial/antwerp-ets-band.png',
        'logo-icon' => 'images/logos/logo-icon.jpg',
        'logo-emblem' => 'images/logos/logo-emblem.jpg',
    ];

    /**
     * Resolve a manifest asset key to its public path, if known.
     */
    public static function assetPath(string $key): ?string
    {
        return self::ASSET_PATHS[$key] ?? null;
    }

    /**
     * Resolve a manifest asset key to a browser-ready URL, if known.
     */
    public static function assetUrl(string $key): ?string
    {
        $path = self::assetPath($key);

        return $path !== null ? '/'.$path : null;
    }

    /**
     * Paths relative to the public root, e.g. `images/rooms/room-entrance.png`.
     *
     * @return array<int, string>
     */
    public static function existingPaths(): array
    {
        $root = public_path(self::DIRECTORY);

        if (! File::isDirectory($root)) {
            return [];
        }

        $paths = [];

        foreach (File::allFiles($root) as $file) {
            if (! in_array(strtolower($file->getExtension()), self::EXTENSIONS, true)) {
                continue;
            }

            $paths[] = self::DIRECTORY.'/'.str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $file->getRelativePathname(),
            );
        }

        sort($paths);

        return $paths;
    }
}
