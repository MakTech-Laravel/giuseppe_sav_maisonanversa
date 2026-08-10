<?php

use Illuminate\Support\Facades\File;

/*
 * The cinematic layer is a set of exact numbers taken from the prototype, and
 * "close enough" is indistinguishable from correct until someone compares the
 * two sites side by side. These tests pin the values down in the two places
 * they live: the stylesheet, for the CSS-only effects, and the GSAP module,
 * for the eases and media contexts everything else is declared against.
 */

function stylesheet(): string
{
    return File::get(resource_path('css/app.css'));
}

function gsapModule(): string
{
    return File::get(resource_path('js/lib/gsap.ts'));
}

/**
 * Every block guarded by the given media query, concatenated, so a value can be
 * asserted to sit behind that query rather than merely somewhere in the file.
 * A condition may legitimately appear more than once — reduced motion applies
 * to both the base layer and the components layer.
 */
function mediaBlocks(string $condition): string
{
    $css = stylesheet();
    $needle = "@media {$condition}";
    $blocks = '';
    $offset = 0;

    while (($start = strpos($css, $needle, $offset)) !== false) {
        $depth = 0;

        for ($i = strpos($css, '{', $start); $i < strlen($css); $i++) {
            $depth += match ($css[$i]) {
                '{' => 1,
                '}' => -1,
                default => 0,
            };

            if ($depth === 0) {
                $blocks .= substr($css, $start, $i - $start + 1);
                $offset = $i;

                break;
            }
        }

        if ($depth !== 0) {
            break;
        }
    }

    expect($blocks)->not->toBeEmpty("No `{$needle}` block found.");

    return $blocks;
}

test('the film grain keeps its noise tile and stepped jitter', function () {
    // Easing between the six positions would read as a blur, not as grain.
    expect(stylesheet())
        ->toContain('animation: cine-grain 0.5s steps(1) infinite')
        ->toContain("baseFrequency='0.9'")
        ->toContain("numOctaves='3'")
        ->toContain("width='200' height='200'");
});

test('the grain and vignette soften on a small screen', function () {
    $mobile = mediaBlocks('(max-width: 560px)');

    expect($mobile)
        ->toContain('opacity: 0.025')
        ->toContain('transparent 50%')
        ->toContain('rgba(41, 28, 24, 0.3)');
});

test('the marquee travels exactly half its width so the loop is seamless', function () {
    expect(stylesheet())
        ->toContain('animation: marquee 30s linear infinite')
        ->toContain('translateX(-50%)');
});

test('the cursor blends against the page and ignores the pointer', function () {
    expect(stylesheet())
        ->toContain('mix-blend-mode: difference')
        ->toContain('pointer-events: none');
});

test('the cursor is hidden where there is no pointer to follow', function () {
    expect(mediaBlocks('(pointer: coarse)'))
        ->toContain('.cine-cursor')
        ->toContain('display: none');
});

test('reduced motion silences every looping effect', function (string $selector) {
    // The prototype stopped only the grain here; the other two kept running.
    expect(mediaBlocks('(prefers-reduced-motion: reduce)'))->toContain($selector);
})->with([
    '.cine-grain',
    '.marquee-track',
    '.gold-rule',
    "[data-slot='accordion-content']",
    "[data-slot='accordion-trigger'] > svg",
]);

test('reduced motion stops animation rather than merely slowing it', function () {
    expect(mediaBlocks('(prefers-reduced-motion: reduce)'))
        ->toContain('animation: none')
        ->toContain('display: none');
});

test('reduced motion turns off smooth scrolling', function () {
    expect(mediaBlocks('(prefers-reduced-motion: reduce)'))
        ->toContain('scroll-behavior: auto');
});

test('the brand easing curves are reproduced exactly, not approximated', function () {
    expect(gsapModule())
        ->toContain("'0.2,0.8,0.2,1'")
        ->toContain("'0.4,0,0.2,1'");
});

test('every media context requires an explicit motion preference', function () {
    preg_match_all("/^\s{4}\w+:\s*\n?\s*'([^']+)'/m", gsapModule(), $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $query) {
        // Reduced motion is the absence of every context, never a variant.
        expect($query)->toContain('prefers-reduced-motion: no-preference');
    }
});

test('gsap plugins are registered once, in the shared module', function () {
    $registrations = 0;

    foreach (File::allFiles(resource_path('js')) as $file) {
        $registrations += substr_count(
            File::get($file->getPathname()),
            'registerPlugin',
        );
    }

    expect($registrations)->toBe(1);
});
