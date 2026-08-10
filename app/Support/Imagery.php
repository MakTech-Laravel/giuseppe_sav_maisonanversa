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
