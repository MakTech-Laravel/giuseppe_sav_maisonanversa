<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;

test('the admin sidebar includes administrator, roles, and permissions', function () {
    $source = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    foreach ([
        "t('Dashboard')",
        "t('Klanten')",
        "t('Bestellingen')",
        "t('Gemeenschap')",
        "t('Heritage Letter')",
        "t('Journal')",
        "t('Beheerder')",
        "t('Rollen')",
        "t('Rechten')",
        "t('Profiel & Beveiliging')",
    ] as $needle) {
        expect($source)->toContain($needle);
    }

    expect($source)
        ->not->toContain("t('Profiel')")
        ->not->toContain("t('Beveiliging')")
        ->not->toContain("t('Access Control')")
        ->not->toContain("t('Admins')")
        ->not->toContain("t('Berichten')")
        ->not->toContain('File Upload Demo')
        ->not->toContain("href: '#'");
});

test('sidebar nav items are gated on the same permission their route enforces', function () {
    $source = file_get_contents(resource_path('js/components/app-sidebar.tsx'));

    // Each pair is [nav item title, permission constant the route actually requires].
    // A mismatch here means a staff member either sees a link that 403s, or has
    // access to a page with no way to navigate to it.
    foreach ([
        ["t('Bestellingen')", 'PERMISSIONS.ORDERS.MANAGE'],
        ["t('Product')", 'PERMISSIONS.HERITAGE.VIEW'],
        ["t('Editievoorraad')", 'PERMISSIONS.HERITAGE.VIEW'],
        ["t('Partner Clubs')", 'PERMISSIONS.HERITAGE.VIEW'],
        ["t('FAQ')", 'PERMISSIONS.HERITAGE.VIEW'],
        ["t('Afspraken')", 'PERMISSIONS.HERITAGE.VIEW'],
        ["t('Feedback')", 'PERMISSIONS.HERITAGE.VIEW'],
        ["t('Kleedkamer')", 'PERMISSIONS.HERITAGE.VIEW'],
        ["t('Site-instellingen')", 'PERMISSIONS.HERITAGE.VIEW'],
        ["t('SEO Meta')", 'PERMISSIONS.HERITAGE.VIEW'],
        ["t('Rollen')", 'PERMISSIONS.ROLES.INDEX'],
        ["t('Rechten')", 'PERMISSIONS.PERMISSIONS.INDEX'],
    ] as [$title, $permission]) {
        $titlePosition = strpos($source, $title);
        expect($titlePosition)->not->toBeFalse();

        $permissionsLinePosition = strpos($source, 'permissions:', $titlePosition);
        $nextItemBoundary = strpos($source, 'title:', $titlePosition + strlen($title));
        $window = $nextItemBoundary === false
            ? substr($source, $titlePosition)
            : substr($source, $titlePosition, $nextItemBoundary - $titlePosition);

        expect($permissionsLinePosition)->not->toBeFalse()
            ->and($window)->toContain($permission);
    }
});

test('a staff member with only heritage.view can open every heritage-gated admin page', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);
    $this->seed(PermissionSeeder::class);

    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo('heritage.view');

    foreach ([
        'admin.products.index',
        'admin.heritage.index',
        'admin.partner-clubs.index',
        'admin.faqs.index',
        'admin.appointments.index',
        'admin.feedback.index',
        'admin.dressing-items.index',
        'admin.site-settings.edit',
        'admin.legal-pages.index',
        'admin.seo-metas.index',
    ] as $routeName) {
        $this->actingAs($staff)
            ->get(route($routeName, ['locale' => 'nl']))
            ->assertOk();
    }

    // dashboard.view is a distinct permission — heritage.view alone must not grant it.
    $this->actingAs($staff)
        ->get(route('admin.dashboard', ['locale' => 'nl']))
        ->assertForbidden();
});

test('a staff member with only orders.manage can open the orders admin page', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);
    $this->seed(PermissionSeeder::class);

    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo('orders.manage');

    $this->actingAs($staff)
        ->get(route('admin.orders.index', ['locale' => 'nl']))
        ->assertOk();
});

test('a staff member with only community.moderate can open the community admin page', function () {
    config(['maison.admin_type_grants_all_permissions' => false]);
    $this->seed(PermissionSeeder::class);

    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo('community.moderate');

    $this->actingAs($staff)
        ->get(route('admin.community.index', ['locale' => 'nl']))
        ->assertOk();
});
