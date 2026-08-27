<?php

use App\Enums\GuardEnum;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::query()->firstOrCreate(
            [
                'name' => PermissionEnum::CLUBS_MANAGE->value,
                'guard_name' => GuardEnum::WEB->value,
            ],
            ['group' => PermissionEnum::CLUBS_MANAGE->group()],
        );

        $role = Role::query()
            ->where('name', RoleEnum::ADMIN->value)
            ->where('guard_name', GuardEnum::WEB->value)
            ->first();

        $role?->givePermissionTo(PermissionEnum::CLUBS_MANAGE->value);
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::query()
            ->where('name', PermissionEnum::CLUBS_MANAGE->value)
            ->where('guard_name', GuardEnum::WEB->value)
            ->delete();
    }
};
