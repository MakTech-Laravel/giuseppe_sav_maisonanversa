<?php

use App\Enums\ClubStatus;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Soft-merge left ClubStatus::Merged tombstones. Hard merge deletes the duplicate,
 * so purge any remaining merged rows after remapping sessions / merged_into chains.
 */
return new class extends Migration
{
    public function up(): void
    {
        $clubType = (new Club)->getMorphClass();

        Club::query()
            ->where('status', ClubStatus::Merged)
            ->orderBy('id')
            ->each(function (Club $merged) use ($clubType): void {
                $survivorId = $merged->merged_into_id;
                $survivorExists = $survivorId !== null
                    && Club::query()->whereKey($survivorId)->exists();

                if ($survivorExists) {
                    CommunitySession::query()
                        ->where('club_id', $merged->id)
                        ->update(['club_id' => $survivorId]);

                    Club::query()
                        ->where('merged_into_id', $merged->id)
                        ->update(['merged_into_id' => $survivorId]);
                } else {
                    CommunitySession::query()
                        ->where('club_id', $merged->id)
                        ->update(['club_id' => null]);

                    Club::query()
                        ->where('merged_into_id', $merged->id)
                        ->update(['merged_into_id' => null]);
                }

                Translation::query()
                    ->where('translatable_type', $clubType)
                    ->where('translatable_id', $merged->id)
                    ->delete();

                if (filled($merged->image_path)) {
                    Storage::disk('public')->delete($merged->image_path);
                }

                $merged->delete();
            });
    }

    public function down(): void
    {
        // Irreversible data cleanup.
    }
};
