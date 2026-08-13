<?php

test('the admin sidebar includes administrator and hides access control', function () {
    $source = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    foreach ([
        "t('Dashboard')",
        "t('Klanten')",
        "t('Bestellingen')",
        "t('Gemeenschap')",
        "t('Heritage Letter')",
        "t('Berichten')",
        "t('Beheerder')",
        "t('Profiel & Beveiliging')",
        "t('Platform')",
    ] as $needle) {
        expect($source)->toContain($needle);
    }

    expect($source)
        ->not->toContain("t('Profiel')")
        ->not->toContain("t('Beveiliging')")
        ->not->toContain("t('Access Control')")
        ->not->toContain("t('Roles')")
        ->not->toContain("t('Permissions')")
        ->not->toContain("t('Admins')")
        ->not->toContain('File Upload Demo')
        ->not->toContain("href: '#'");
});
