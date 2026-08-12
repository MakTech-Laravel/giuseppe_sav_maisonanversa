<?php

use App\Enums\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type')->default(UserType::Customer->value)->after('username');
            $table->index('type');
        });

        $staffRoles = UserType::staffRoleValues();

        $users = DB::table('users')->select('id')->get();

        foreach ($users as $user) {
            $hasStaffRole = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\Models\User')
                ->where('model_has_roles.model_id', $user->id)
                ->whereIn('roles.name', $staffRoles)
                ->exists();

            DB::table('users')->where('id', $user->id)->update([
                'type' => $hasStaffRole ? UserType::Admin->value : UserType::Customer->value,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
