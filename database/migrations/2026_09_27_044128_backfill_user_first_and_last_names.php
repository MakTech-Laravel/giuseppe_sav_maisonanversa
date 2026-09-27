<?php

use App\Models\User;
use App\Support\PersonName;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::query()
            ->whereNull('first_name')
            ->whereNull('last_name')
            ->orderBy('id')
            ->each(function (User $user): void {
                $parts = PersonName::split($user->name);

                $user->forceFill([
                    'first_name' => $parts['first_name'] !== '' ? $parts['first_name'] : null,
                    'last_name' => $parts['last_name'] !== '' ? $parts['last_name'] : null,
                ])->save();
            });
    }

    /**
     * Reverse the migrations.
     *
     * Names stay on the account; rolling back only the schema migration drops the columns.
     */
    public function down(): void
    {
        //
    }
};
