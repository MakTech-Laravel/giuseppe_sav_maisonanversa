<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

test('the user seeder creates ten numbered members whose password is their email', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, UserSeeder::class]);

    foreach (range(1, 10) as $number) {
        $email = "user{$number}@dev.com";
        $user = User::query()->where('email', $email)->sole();

        expect($user->name)->toBe("User {$number}")
            ->and(Hash::check($email, $user->password))->toBeTrue()
            ->and($user->type)->toBe(UserType::Customer)
            ->and($user->hasRole(RoleEnum::USER->value))->toBeTrue()
            ->and($user->email_verified_at)->not->toBeNull();
    }
});

test('running the user seeder twice does not duplicate the numbered members', function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class, UserSeeder::class]);
    $this->seed(UserSeeder::class);

    expect(User::query()->where('email', 'like', 'user%@dev.com')->count())->toBe(11);
});
