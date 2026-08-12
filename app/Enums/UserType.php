<?php

namespace App\Enums;

enum UserType: string
{
    case Customer = 'customer';
    case Admin = 'admin';

    /**
     * @return list<string>
     */
    public static function staffRoleValues(): array
    {
        return [
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::ADMIN->value,
            RoleEnum::EDITOR->value,
            RoleEnum::AUTHOR->value,
            RoleEnum::VIEWER->value,
        ];
    }
}
