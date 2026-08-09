<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
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

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]
            );

            if ($role && RoleEnum::from($role)) {
                $user->syncRoles([$role]);
            }

            $created++;
        }

        $this->command->info("Users: {$created} created.");
    }

    private function users(): array
    {
        return [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@dev.com',
                'password' => 'superadmin@dev.com',
                'role' => RoleEnum::SUPER_ADMIN->value,
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@dev.com',
                'password' => 'admin@dev.com',
                'role' => RoleEnum::ADMIN->value,
            ],
            [
                'name' => 'Editor',
                'email' => 'editor@dev.com',
                'password' => 'editor@dev.com',
                'role' => RoleEnum::EDITOR->value,
            ],
            [
                'name' => 'Author',
                'email' => 'author@dev.com',
                'password' => 'author@dev.com',
                'role' => RoleEnum::AUTHOR->value,
            ],
            [
                'name' => 'Viewer',
                'email' => 'viewer@dev.com',
                'password' => 'viewer@dev.com',
                'role' => RoleEnum::VIEWER->value,
            ],
            [
                'name' => 'User',
                'email' => 'user@dev.com',
                'password' => 'user@dev.com',
                'role' => RoleEnum::USER->value,
            ],
        ];
    }
}
