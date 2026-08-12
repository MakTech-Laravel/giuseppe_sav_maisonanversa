<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $created = 0;

        foreach ($this->users() as $user) {
            $name = $user['name'];
            $email = $user['email'];
            $password = $user['password'];
            $role = $user['role'] ?? null;
            $type = $user['type'] ?? UserType::Customer;

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => User::generateUsername($name),
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'type' => $type,
                ]
            );

            if ($role && RoleEnum::from($role)) {
                $user->syncRoles([$role]);
                $user->syncTypeFromRoles();
            }

            $created++;
        }

        $this->command->info("Users: {$created} created.");
    }

    /**
     * @return list<array{name: string, email: string, password: string, role?: string, type?: UserType}>
     */
    private function users(): array
    {
        return [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@dev.com',
                'password' => 'superadmin@dev.com',
                'role' => RoleEnum::SUPER_ADMIN->value,
                'type' => UserType::Admin,
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@dev.com',
                'password' => 'admin@dev.com',
                'role' => RoleEnum::ADMIN->value,
                'type' => UserType::Admin,
            ],
            [
                'name' => 'Editor',
                'email' => 'editor@dev.com',
                'password' => 'editor@dev.com',
                'role' => RoleEnum::EDITOR->value,
                'type' => UserType::Admin,
            ],
            [
                'name' => 'Author',
                'email' => 'author@dev.com',
                'password' => 'author@dev.com',
                'role' => RoleEnum::AUTHOR->value,
                'type' => UserType::Admin,
            ],
            [
                'name' => 'Viewer',
                'email' => 'viewer@dev.com',
                'password' => 'viewer@dev.com',
                'role' => RoleEnum::VIEWER->value,
                'type' => UserType::Admin,
            ],
            [
                'name' => 'User',
                'email' => 'user@dev.com',
                'password' => 'user@dev.com',
                'role' => RoleEnum::USER->value,
                'type' => UserType::Customer,
            ],
            [
                'name' => 'Customer',
                'email' => 'customer@dev.com',
                'password' => 'customer@dev.com',
                'role' => RoleEnum::USER->value,
                'type' => UserType::Customer,
            ],
        ];
    }
}
