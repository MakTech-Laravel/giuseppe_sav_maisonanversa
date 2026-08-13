<?php

test('the member layout uses maison brand surfaces', function () {
    $layout = file_get_contents(resource_path('js/layouts/member-layout.tsx'));
    $topbar = file_get_contents(resource_path('js/components/member/member-topbar.tsx'));
    $nav = file_get_contents(resource_path('js/components/member/member-nav.tsx'));

    expect($layout)
        ->toContain('bg-choc')
        ->toContain('MemberNav')
        ->toContain('MemberTopbar')
        ->and($topbar)
        ->toContain('bg-choc2')
        ->toContain('sticky')
        ->toContain('Avatar')
        ->toContain('Maison Anversa')
        ->toContain('LanguageSwitcher')
        ->toContain('`/${locale}`')
        ->and($nav)
        ->toContain('md:sticky');
});

test('admin theme tokens use the maison palette', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('--sidebar: #352722')
        ->toContain('--sidebar-primary: #8d705a')
        ->toContain('--background: #f3ebe3');
});

test('the admin shell exposes a language switcher', function () {
    $header = file_get_contents(resource_path('js/components/app-sidebar-header.tsx'));
    $sidebar = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    expect($header)
        ->toContain('LanguageSwitcher')
        ->and($sidebar)
        ->toContain('useTranslation')
        ->toContain("t('Dashboard')")
        ->toContain("t('Klanten')");
});
