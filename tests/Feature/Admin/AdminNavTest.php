<?php

test('the admin sidebar includes administrator and hides access control', function () {
    $source = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    foreach ([
        "title: 'Dashboard'",
        "title: 'Customers'",
        "title: 'Orders'",
        "title: 'Community'",
        "title: 'Heritage Letter'",
        "title: 'Posts'",
        "title: 'Administrator'",
        "title: 'Profile'",
        "title: 'Security'",
    ] as $needle) {
        expect($source)->toContain($needle);
    }

    expect($source)
        ->not->toContain("title: 'Access Control'")
        ->not->toContain("title: 'Roles'")
        ->not->toContain("title: 'Permissions'")
        ->not->toContain("title: 'Admins'")
        ->not->toContain('File Upload Demo')
        ->not->toContain("href: '#'");
});
