<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $collidingEmails = DB::table('users')
            ->selectRaw('lower(email) as email')
            ->groupByRaw('lower(email)')
            ->havingRaw('count(*) > 1')
            ->pluck('email');

        foreach (DB::table('users')->select('id', 'email')->orderBy('id')->cursor() as $user) {
            $email = Str::lower($user->email);

            if ($collidingEmails->contains($email)) {
                continue;
            }

            DB::table('newsletter_subscribers')
                ->whereRaw('lower(email) = ?', [$email])
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('newsletter_subscribers')->update(['user_id' => null]);
    }
};
