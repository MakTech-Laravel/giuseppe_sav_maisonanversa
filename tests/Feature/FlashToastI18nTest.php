<?php

test('http and network flash toast strings have nl and fr dictionary keys', function () {
    $keys = [
        'Request failed',
        'Request failed ({{status}})',
        'Network error. Please try again.',
    ];

    $nl = json_decode(File::get(lang_path('nl.json')), true, flags: JSON_THROW_ON_ERROR);
    $fr = json_decode(File::get(lang_path('fr.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($keys as $key) {
        expect($nl)->toHaveKey($key)
            ->and($fr)->toHaveKey($key)
            ->and($nl[$key])->not->toBe($key)
            ->and($fr[$key])->not->toBe($key);
    }

    expect(File::get(resource_path('js/hooks/use-flash-toast.ts')))
        ->toContain("translate('Request failed ({{status}})'")
        ->toContain("translate('Network error. Please try again.')")
        ->toContain('translate(data.message)')
        ->toContain('pendingVisitLocale')
        ->toContain('resolveToastLocale')
        ->not->toContain('`Verzoek mislukt (${status})`');

    expect(File::get(resource_path('js/hooks/use-locale.ts')))
        ->toContain('switchInstanceLocale(i18n, next)')
        ->not->toContain('await loadDictionary(next)');
});

test('admin and settings flash toast strings use english keys with nl and fr entries', function () {
    $keys = [
        'Administrator created.',
        'Administrator updated.',
        'Administrator deleted.',
        'The super-admin role cannot be deleted.',
        'The last super administrator account cannot be deleted.',
        'Customer created.',
        'Customer updated.',
        'Customer deleted.',
        'Private realtime channel',
        'Private channel test received.',
        'Profile updated.',
        'Realtime notification received.',
        'Role created.',
        'Role updated.',
        'Role deleted.',
        'Password updated.',
    ];

    $nl = json_decode(File::get(lang_path('nl.json')), true, flags: JSON_THROW_ON_ERROR);
    $fr = json_decode(File::get(lang_path('fr.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($keys as $key) {
        expect($nl)->toHaveKey($key)
            ->and($fr)->toHaveKey($key)
            ->and($nl[$key])->not->toBe($key)
            ->and($fr[$key])->not->toBe($key);
    }

    expect(File::get(app_path('Http/Controllers/Admin/CustomerController.php')))
        ->toContain("__('Customer created.')")
        ->not->toContain('Customer created successfully.');

    expect(File::get(app_path('Http/Controllers/Admin/RoleController.php')))
        ->toContain("__('Role created.')")
        ->not->toContain('Role created successfully.');

    expect(File::get(app_path('Http/Controllers/Admin/UserController.php')))
        ->toContain("__('Administrator created.')")
        ->not->toContain('Administrator created successfully.');

    expect(File::get(app_path('Http/Controllers/Settings/ProfileController.php')))
        ->toContain("__('Profile updated.')");

    expect(File::get(app_path('Http/Controllers/Settings/SecurityController.php')))
        ->toContain("__('Password updated.')");

    expect(File::get(resource_path('js/components/notifications/notification-toast.tsx')))
        ->toContain('t(payload.title)')
        ->toContain('t(payload.message)');

    expect(File::get(resource_path('js/hooks/use-private-realtime-toast.ts')))
        ->toContain("t('Private realtime channel')");
});
