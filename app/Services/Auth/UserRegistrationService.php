<?php

namespace App\Services\Auth;

use App\Enums\GuardEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserRegistrationService
{
    /**
     * Create a new customer account.
     *
     * @param  array{name: string, email: string, password: string}  $input
     */
    public function register(array $input): User
    {
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'username' => User::generateUsername($input['name']),
            'password' => $input['password'],
            'type' => UserType::Customer,
            'locale' => app()->getLocale(),
        ]);

        Role::findOrCreate(RoleEnum::USER->value, GuardEnum::WEB->value);
        $user->assignRole(RoleEnum::USER->value);

        return $user;
    }
}
