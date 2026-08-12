<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        $users = DB::table('users')->select('id', 'name')->get();

        foreach ($users as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'username' => $this->uniqueUsername((string) $user->name, (int) $user->id),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    private function uniqueUsername(string $name, int $id): string
    {
        $base = Str::slug(Str::lower($name), '_');
        $base = preg_replace('/[^a-z0-9_]/', '', $base) ?: 'member';
        $base = Str::limit($base, 20, '');

        $candidate = $base.'_'.$id;

        $suffix = 1;
        while (DB::table('users')->where('username', $candidate)->where('id', '!=', $id)->exists()) {
            $candidate = $base.'_'.$id.'_'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
};
