<?php

use Illuminate\Support\Facades\File;

/*
 * The primitives carry the measurements every page is then composed from, so a
 * wrong number here is wrong everywhere at once and reads as "nearly right"
 * rather than as a bug. These tests pin the handful of values taken verbatim
 * from the prototype.
 */

function primitive(string $name): string
{
    return File::get(resource_path("js/components/maison/ui/{$name}.tsx"));
}

test('the measure is 1280px wide with the prototype\'s two gutters', function () {
    // max-w-320 is 1280px; px-8 and md:px-20 are the 32px and 80px gutters.
    expect(primitive('section'))
        ->toContain('max-w-320')
        ->toContain('px-8')
        ->toContain('md:px-20');
});

test('a section offers all four grounds, each with its own text colour', function (string $tone) {
    expect(primitive('section'))->toContain($tone);
})->with([
    'bg-cream text-choc',
    'bg-cream2 text-choc',
    'bg-choc text-cream',
    'bg-choc2 text-cream',
]);

test('the eyebrow keeps its 9px size and wide tracking', function () {
    expect(primitive('eyebrow'))
        ->toContain('text-[9px]')
        ->toContain('tracking-[0.35em]')
        ->toContain('uppercase');
});

test('the gold rule is 40x1 and leaves its pulse to the stylesheet', function () {
    // The animation belongs in CSS so the reduced-motion query can reach it.
    expect(primitive('gold-rule'))
        ->toContain('gold-rule')
        ->toContain('w-10')
        ->toContain('h-px')
        ->not->toContain('animate-');
});

test('the page hero keeps its type ramp, ground and drafting grid', function () {
    expect(primitive('page-hero'))
        ->toContain('clamp(36px,5vw,68px)')
        ->toContain('bg-choc2')
        ->toContain('48px_48px');
});

test('the page hero italicises an emphasised word in gold', function () {
    expect(primitive('page-hero'))
        ->toContain('[&_em]:text-gold')
        ->toContain('[&_em]:italic');
});

test('the button covers every ground the prototype had a class for', function (string $variant) {
    expect(primitive('maison-button'))->toContain($variant);
})->with([
    'hero',
    'choc',
    'gold',
    'filled',
    'outlineCream',
    'outlineChoc',
    'ghost',
]);

test('a button can fill its column, which half the prototype\'s classes existed to do', function () {
    expect(primitive('maison-button'))->toContain('block: {');
    expect(primitive('maison-button'))->toContain('w-full');
});

test('the reveal marks itself for the scroll observer without hiding itself', function () {
    /*
     * The prototype's `.reveal` set `opacity: 0` in CSS, so a failed script left
     * the page blank. Here the starting state is applied by GSAP instead.
     */
    expect(primitive('reveal'))
        ->toContain('data-reveal')
        ->not->toContain('opacity-0');
});

test('the success panel announces itself to a screen reader', function () {
    expect(primitive('success-panel'))
        ->toContain('aria-live="polite"')
        ->toContain('role="status"');
});

test('the monogram offers the three sizes the community pages use', function (string $size) {
    expect(primitive('monogram'))->toContain($size);
})->with(['size-9', 'size-10', 'size-11']);

test('the accordion is built on the keyboard-capable primitive', function () {
    /*
     * The prototype toggled a class on a div, so the questions were not
     * focusable and a closed answer stayed in the tab order.
     */
    expect(primitive('maison-accordion'))
        ->toContain('radix-ui')
        ->toContain('AccordionPrimitive.Trigger')
        ->toContain('collapsible');
});

test('the accordion keeps the house plus that rotates into a cross', function () {
    expect(primitive('maison-accordion'))
        ->toContain('[&[data-state=open]>svg]:rotate-45')
        ->not->toContain('Chevron');
});

test('every primitive is reachable under one directory', function () {
    $files = collect(File::files(resource_path('js/components/maison/ui')))
        ->map(fn ($file) => $file->getFilename())
        ->sort()
        ->values()
        ->all();

    expect($files)->toBe([
        'eyebrow.tsx',
        'gold-rule.tsx',
        'hairline.tsx',
        'maison-accordion.tsx',
        'maison-button.tsx',
        'monogram.tsx',
        'page-hero.tsx',
        'reveal.tsx',
        'section.tsx',
        'stat.tsx',
        'success-panel.tsx',
        'text-link.tsx',
    ]);
});
