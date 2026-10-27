<?php

test('http and network flash toast strings have en and fr dictionary keys', function () {
    $keys = [
        'Verzoek mislukt',
        'Verzoek mislukt ({{status}})',
        'Netwerkfout. Probeer het opnieuw.',
    ];

    $en = json_decode(File::get(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
    $fr = json_decode(File::get(lang_path('fr.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key)
            ->and($fr)->toHaveKey($key)
            ->and($en[$key])->not->toBe($key)
            ->and($fr[$key])->not->toBe($key);
    }

    expect(File::get(resource_path('js/hooks/use-flash-toast.ts')))
        ->toContain("translate('Verzoek mislukt ({{status}})'")
        ->toContain("translate('Netwerkfout. Probeer het opnieuw.')")
        ->toContain('translate(data.message)')
        ->not->toContain('`Verzoek mislukt (${status})`');
});

test('admin and settings flash toast strings use dutch keys with en and fr entries', function () {
    $keys = [
        'Beheerder aangemaakt.',
        'Beheerder bijgewerkt.',
        'Beheerder verwijderd.',
        'De super-adminrol kan niet worden verwijderd.',
        'Het laatste superbeheerderaccount kan niet worden verwijderd.',
        'Klant aangemaakt.',
        'Klant bijgewerkt.',
        'Klant verwijderd.',
        'Privé realtime-kanaal',
        'Privé-kanaal test ontvangen.',
        'Profiel bijgewerkt.',
        'Realtime melding ontvangen.',
        'Rol aangemaakt.',
        'Rol bijgewerkt.',
        'Rol verwijderd.',
        'Wachtwoord bijgewerkt.',
    ];

    $en = json_decode(File::get(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
    $fr = json_decode(File::get(lang_path('fr.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key)
            ->and($fr)->toHaveKey($key)
            ->and($en[$key])->not->toBe($key)
            ->and($fr[$key])->not->toBe($key);
    }

    expect(File::get(app_path('Http/Controllers/Admin/CustomerController.php')))
        ->toContain("__('Klant aangemaakt.')")
        ->not->toContain('Customer created successfully.');

    expect(File::get(app_path('Http/Controllers/Admin/RoleController.php')))
        ->toContain("__('Rol aangemaakt.')")
        ->not->toContain('Role created successfully.');

    expect(File::get(app_path('Http/Controllers/Admin/UserController.php')))
        ->toContain("__('Beheerder aangemaakt.')")
        ->not->toContain('Administrator created successfully.');

    expect(File::get(app_path('Http/Controllers/Settings/ProfileController.php')))
        ->toContain("__('Profiel bijgewerkt.')")
        ->not->toContain("__('Profile updated.')");

    expect(File::get(app_path('Http/Controllers/Settings/SecurityController.php')))
        ->toContain("__('Wachtwoord bijgewerkt.')")
        ->not->toContain("__('Password updated.')");

    expect(File::get(resource_path('js/components/notifications/notification-toast.tsx')))
        ->toContain('t(payload.title)')
        ->toContain('t(payload.message)');

    expect(File::get(resource_path('js/hooks/use-private-realtime-toast.ts')))
        ->toContain("t('Privé realtime-kanaal')")
        ->not->toContain('Private realtime channel');
});
