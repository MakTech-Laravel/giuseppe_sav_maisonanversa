<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inquiries')
            ->where('type', 'contact')
            ->orderBy('id')
            ->chunkById(100, function ($inquiries): void {
                foreach ($inquiries as $inquiry) {
                    $type = $this->typeFromSubject((string) $inquiry->subject);

                    if ($type === 'contact') {
                        continue;
                    }

                    DB::table('inquiries')
                        ->where('id', $inquiry->id)
                        ->update(['type' => $type]);
                }
            });
    }

    public function down(): void
    {
        DB::table('inquiries')
            ->whereIn('type', ['appointment', 'consult', 'feedback'])
            ->update(['type' => 'contact']);
    }

    private function typeFromSubject(string $subject): string
    {
        $normalized = Str::lower($subject);

        if (Str::contains($normalized, 'feedback')) {
            return 'feedback';
        }

        if (Str::contains($normalized, ['consult', 'privé', 'prive'])) {
            return 'consult';
        }

        if (Str::contains($normalized, ['afspraak', 'appointment', 'rendez-vous', 'rendez vous'])) {
            return 'appointment';
        }

        return 'contact';
    }
};
