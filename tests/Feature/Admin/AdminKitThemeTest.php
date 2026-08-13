<?php

test('the admin kit applies the inverted shell class on the app shell', function () {
    $source = file_get_contents(resource_path('js/components/app-shell.tsx'));

    expect($source)->toContain('admin-kit');
});

test('admin kit palette tokens are defined for cream sidebar and chocolate content', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('.admin-kit')
        ->toContain('--sidebar: #cfc0ae')
        ->toContain('--background: #291c18')
        ->toContain('--color-sidebar: var(--sidebar)')
        ->toContain('--color-background: var(--background)')
        ->toContain('color: var(--foreground)');
});
