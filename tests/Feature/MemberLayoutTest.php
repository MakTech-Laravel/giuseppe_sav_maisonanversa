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
        ->not->toContain('MemberNotificationBell')
        ->not->toContain('member-notification-bell')
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

test('the admin user menu uses the chocolate shell palette', function () {
    $navUser = file_get_contents(resource_path('js/components/nav-user.tsx'));
    $authMenu = file_get_contents(resource_path('js/components/maison/shell/auth-menu.tsx'));
    $menu = file_get_contents(resource_path('js/components/user-menu-content.tsx'));
    $info = file_get_contents(resource_path('js/components/user-info.tsx'));

    expect($navUser)
        ->toContain('admin-kit')
        ->toContain('bg-sidebar')
        ->toContain('auth?.user')
        ->and($authMenu)
        ->toContain('auth?.user')
        ->and(file_get_contents(resource_path('js/components/maison/placeholder-image.tsx')))
        ->toContain('availableImages ?? []')
        ->and(file_get_contents(resource_path('js/hooks/use-locale.ts')))
        ->toContain('availableLocales ?? [...LOCALES]')
        ->and(file_get_contents(resource_path('js/components/maison/seo/maison-seo-head.tsx')))
        ->toContain('if (!appUrl || !seo)')
        ->and($menu)
        ->toContain('focus:bg-sidebar-accent')
        ->and($info)
        ->toContain('bg-sidebar-accent')
        ->toContain('text-sidebar-foreground');
});
