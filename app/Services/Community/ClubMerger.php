<?php

namespace App\Services\Community;

use App\Enums\ClubStatus;
use App\Models\Club;
use App\Models\CommunitySession;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Two members can add the same venue under slightly different names. Merging
 * repoints every session onto the surviving club and keeps the duplicate as a
 * tombstone so old links still resolve.
 */
final class ClubMerger
{
    public function merge(Club $duplicate, Club $survivor): void
    {
        if ($duplicate->is($survivor)) {
            throw new RuntimeException('A club cannot be merged into itself.');
        }

        DB::transaction(function () use ($duplicate, $survivor): void {
            CommunitySession::query()
                ->where('club_id', $duplicate->id)
                ->update(['club_id' => $survivor->id]);

            // Chains stay flat: anything already pointing at the duplicate
            // follows it onto the survivor.
            Club::query()
                ->where('merged_into_id', $duplicate->id)
                ->update(['merged_into_id' => $survivor->id]);

            $duplicateWasPartner = $duplicate->is_partner;

            $duplicate->update([
                'status' => ClubStatus::Merged,
                'merged_into_id' => $survivor->id,
                'is_partner' => false,
            ]);

            if ($duplicateWasPartner && ! $survivor->is_partner) {
                $survivor->update(['is_partner' => true]);
            }
        });
    }
}
