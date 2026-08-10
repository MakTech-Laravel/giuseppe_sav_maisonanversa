<?php

use Illuminate\Support\Facades\File;

test('the public stylesheet publishes brand-palette focus rings', function () {
    $css = File::get(resource_path('css/app.css'));

    expect($css)
        ->toContain(':focus-visible')
        ->toContain('var(--color-gold)');
});

test('the site keeps Tailwind dark utilities inert on OS dark preference', function () {
    $css = File::get(resource_path('css/app.css'));

    expect($css)->toContain('@custom-variant dark (&:is(.dark *))');
});

test('maison components do not opt into Tailwind dark mode utilities', function () {
    $withDarkUtilities = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        $path = str_replace('\\', '/', $file->getPathname());

        if (! str_contains($path, '/maison') || ! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        if (preg_match('/\bdark:[a-z-]/', $file->getContents()) === 1) {
            $withDarkUtilities[] = $file->getFilename();
        }
    }

    expect($withDarkUtilities)->toBe([]);
});

test('the root template stays light mode only', function () {
    $html = File::get(resource_path('views/app.blade.php'));

    expect($html)
        ->toContain('light-mode only')
        ->not->toMatch('/class=["\'][^"\']*\bdark\b/');
});

test('maison source does not mount a document-level dark class', function () {
    $withDocumentDark = [];

    foreach (File::allFiles(resource_path('js')) as $file) {
        $path = str_replace('\\', '/', $file->getPathname());

        if (! str_contains($path, '/maison') || ! in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            continue;
        }

        $source = $file->getContents();

        if (preg_match('/(?:className=["\'][^"\']*\bdark\b|classList\.(?:add|toggle)\(["\']dark["\']\))/', $source) === 1) {
            $withDocumentDark[] = $file->getFilename();
        }
    }

    expect($withDocumentDark)->toBe([]);
});

test('dressing is part of the navigation manifest and page registry', function () {
    $navigation = File::get(resource_path('js/lib/maison-navigation.ts'));

    expect($navigation)
        ->toContain("'dressing'")
        ->toContain("page: 'dressing'");

    preg_match('/MAISON_PAGES = \[(.*?)\] as const;/s', $navigation, $pages);

    expect($pages[1] ?? '')->toContain("'dressing'");
});

test('the dressing page resolves under every locale prefix', function () {
    foreach (config('maison.locales') as $locale) {
        $this->get("/{$locale}/dressing")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('maison/dressing'));
    }
});

test('primary shell controls meet the forty-four pixel touch target', function () {
    $targets = [
        'site-topbar.tsx' => ['min-h-11', 'size-11'],
        'language-switcher.tsx' => ['min-h-11', 'min-w-11'],
        'site-nav.tsx' => ['min-h-11'],
        'contact-dock.tsx' => ['min-h-11'],
        'site-footer.tsx' => ['min-h-11'],
        'maison-button.tsx' => ['min-h-11'],
    ];

    foreach ($targets as $file => $needles) {
        $source = File::get(resource_path('js/components/maison/'.(
            str_contains($file, 'maison-button') ? "ui/{$file}" : "shell/{$file}"
        )));

        foreach ($needles as $needle) {
            expect($source)->toContain($needle);
        }
    }
});

test('site navigation derives the active state from the current url', function () {
    $source = File::get(resource_path('js/components/maison/shell/site-nav.tsx'));

    expect($source)
        ->toContain('activePage(url, locale)')
        ->toContain('aria-current=')
        ->toContain("current === page ? 'page' : undefined");
});

test('site navigation keeps a solid chocolate bar with menu and language switcher', function () {
    $source = File::get(resource_path('js/components/maison/shell/site-nav.tsx'));

    expect($source)
        ->toContain('bg-choc')
        ->toContain('LanguageSwitcher')
        ->toContain('PRIMARY_NAV')
        ->toContain('md:items-center')
        ->toContain('md:gap-8')
        ->toContain('text-gold')
        ->not->toContain('backdrop-blur')
        ->not->toContain('bg-choc/96')
        ->not->toContain('bg-choc/99');
});

test('the public shell mounts the site nav on every maison page', function () {
    $source = File::get(resource_path('js/layouts/frontend-layout.tsx'));

    expect($source)
        ->toContain('<SiteNav />')
        ->toContain('<SiteTopbar');
});
