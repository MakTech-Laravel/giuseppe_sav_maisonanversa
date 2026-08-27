<?php

use App\Enums\GuardEnum;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncUserRole(RoleEnum::USER->permissions());
    }

    public function down(): void
    {
        $this->syncUserRole([
            PermissionEnum::DASHBOARD_VIEW,
            PermissionEnum::POSTS_VIEW,
        ]);
    }

    /**
     * @param  list<PermissionEnum>  $permissions
     */
    private function syncUserRole(array $permissions): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::query()
            ->where('name', RoleEnum::USER->value)
            ->where('guard_name', GuardEnum::WEB->value)
            ->first();

        if ($role === null) {
            return;
        }

        $role->syncPermissions(array_map(
            static fn (PermissionEnum $permission): string => $permission->value,
            $permissions,
        ));
    }
};
