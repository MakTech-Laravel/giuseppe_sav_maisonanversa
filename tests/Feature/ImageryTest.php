<?php

use App\Support\Imagery;
use Illuminate\Support\Facades\File;

/**
 * Paths the front-end manifest expects, read straight out of the manifest so
 * the two cannot drift apart.
 *
 * @return array<int, string>
 */
function manifestImagePaths(): array
{
    $source = File::get(resource_path('js/lib/imagery.ts'));

    preg_match_all("/path: '([^']+)'/", $source, $matches);

    return $matches[1];
}

afterEach(function () {
    foreach (glob(public_path('images/*/__test-*')) as $leftover) {
        File::delete($leftover);
    }
});

test('the manifest documents all seventeen images', function () {
    expect(manifestImagePaths())->toHaveCount(17);
});

test('every manifest path is a public image under a directory that exists', function () {
    foreach (manifestImagePaths() as $path) {
        expect($path)->toStartWith('images/')
            ->and(pathinfo($path, PATHINFO_EXTENSION))->toBeIn(['png', 'jpg']);

        expect(public_path(dirname($path)))->toBeDirectory(
            "The drop-in directory for {$path} is missing.",
        );
    }
});

test('dropped-in photographs are reported as available', function () {
    expect(Imagery::existingPaths())
        ->toContain('images/rooms/room-entrance.png')
        ->toContain('images/brand/hero-mansion.png');
});

test('a file copied into public/images is reported as available', function () {
    File::put(public_path('images/rooms/__test-room.png'), 'not really a png');

    expect(Imagery::existingPaths())->toContain('images/rooms/__test-room.png');
});

test('non-image files are ignored', function () {
    File::put(public_path('images/rooms/__test-notes.txt'), 'notes');

    expect(Imagery::existingPaths())->not->toContain('images/rooms/__test-notes.txt');
});

test('the available images are shared with the front end', function () {
    File::put(public_path('images/brand/__test-facade.png'), 'not really a png');

    $this->get('/nl')->assertInertia(fn ($page) => $page
        ->where('availableImages', fn ($images) => collect($images)->contains('images/brand/__test-facade.png')
            && collect($images)->contains('images/rooms/room-entrance.png'))
    );
});
