<?php

test('the admin sidebar includes the recommended staff navigation', function () {
    $source = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    foreach ([
        "title: 'Dashboard'",
        "title: 'Customers'",
        "title: 'Orders'",
        "title: 'Community'",
        "title: 'Heritage Letter'",
        "title: 'Posts'",
        "title: 'Admins'",
        "title: 'Roles'",
        "title: 'Permissions'",
        "title: 'Profile'",
        "title: 'Security'",
    ] as $needle) {
        expect($source)->toContain($needle);
    }

    expect($source)
        ->not->toContain('File Upload Demo')
        ->not->toContain("href: '#'")
        ->not->toContain('github.com')
        ->not->toContain("title: 'Users'");
});
