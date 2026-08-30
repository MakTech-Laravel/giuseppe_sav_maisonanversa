<?php

namespace App\Services\Auth;

use App\Enums\GuardEnum;
use App\Enums\RoleEnum;
use App\Enums\UserGender;
use App\Enums\UserType;
use App\Models\User;
use App\Services\Newsletter\HeritageLetterSubscription;
use Spatie\Permission\Models\Role;

class UserRegistrationService
{
    public function __construct(public HeritageLetterSubscription $heritageLetter) {}

    /**
     * Create a new customer account.
     *
     * @param  array{name: string, email: string, password: string, gender: string}  $input
     */
    public function register(array $input): User
    {
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'username' => User::generateUsername($input['name']),
            'gender' => UserGender::from($input['gender']),
            'password' => $input['password'],
            'type' => UserType::Customer,
            'locale' => app()->getLocale(),
        ]);

        Role::findOrCreate(RoleEnum::USER->value, GuardEnum::WEB->value);
        $user->assignRole(RoleEnum::USER->value);

        $this->heritageLetter->claimForUser($user);

        return $user;
    }
}
