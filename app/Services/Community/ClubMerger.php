<?php

namespace App\Services\Community;

use App\Enums\ClubStatus;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Hard-merge two clubs: apply the chosen field/feature payload onto the survivor,
 * repoint sessions and translations, then permanently delete the duplicate.
 */
final class ClubMerger
{
    /**
     * Scalar attributes resolved from the survivor/duplicate pair via field sources.
     *
     * @var list<string>
     */
    private const SOURCEABLE_FIELDS = [
        'name',
        'street',
        'postal_code',
        'city',
        'country',
        'lat',
        'lng',
        'website',
        'phone',
        'status',
        'corner_pipeline_status',
        'corner_title',
        'corner_body',
        'corner_location',
        'sort_order',
    ];

    /**
     * Corner columns whose DeepL translations follow the chosen field source.
     *
     * @var list<string>
     */
    private const CORNER_TRANSLATION_COLUMNS = [
        'corner_title',
        'corner_body',
        'corner_location',
    ];

    /**
     * @param  array{
     *     sources: array<string, 'survivor'|'duplicate'>,
     *     image_source: 'survivor'|'duplicate'|'none',
     *     is_partner: bool,
     *     is_session_venue: bool,
     *     has_corner: bool,
     *     corner_published: bool,
     *     show_on_corner_page: bool,
     *     sports: list<string>
     * }  $payload
     */
    public function merge(Club $duplicate, Club $survivor, array $payload): void
    {
        if ($duplicate->is($survivor)) {
            throw new RuntimeException('A club cannot be merged into itself.');
        }

        if ($duplicate->status === ClubStatus::Merged || $survivor->status === ClubStatus::Merged) {
            throw new RuntimeException('A merged club cannot take part in another merge.');
        }

        DB::transaction(function () use ($duplicate, $survivor, $payload): void {
            $survivorOriginalImage = $survivor->image_path;
            $duplicateImage = $duplicate->image_path;

            $attributes = $this->resolveAttributes($duplicate, $survivor, $payload);
            $attributes['image_path'] = $this->resolveImagePath($duplicate, $survivor, $payload['image_source']);

            $survivor->update($attributes);

            CommunitySession::query()
                ->where('club_id', $duplicate->id)
                ->update(['club_id' => $survivor->id]);

            Club::query()
                ->where('merged_into_id', $duplicate->id)
                ->update(['merged_into_id' => $survivor->id]);

            $this->remapCornerTranslations($duplicate, $survivor, $payload['sources']);

            $keptPath = $attributes['image_path'] ?? null;
            $this->deleteUnusedImage($duplicateImage, $keptPath);
            $this->deleteUnusedImage($survivorOriginalImage, $keptPath);

            $duplicate->delete();
        });
    }

    /**
     * @param  array{
     *     sources: array<string, 'survivor'|'duplicate'>,
     *     is_partner: bool,
     *     is_session_venue: bool,
     *     has_corner: bool,
     *     corner_published: bool,
     *     show_on_corner_page: bool,
     *     sports: list<string>
     * }  $payload
     * @return array<string, mixed>
     */
    private function resolveAttributes(Club $duplicate, Club $survivor, array $payload): array
    {
        $attributes = [];

        foreach (self::SOURCEABLE_FIELDS as $field) {
            $source = $payload['sources'][$field] ?? 'survivor';
            $club = $source === 'duplicate' ? $duplicate : $survivor;
            $attributes[$field] = $club->getAttribute($field);
        }

        $attributes['is_partner'] = $payload['is_partner'];
        $attributes['is_session_venue'] = $payload['is_session_venue'];
        $attributes['has_corner'] = $payload['has_corner'];
        $attributes['corner_published'] = $payload['corner_published'];
        $attributes['show_on_corner_page'] = $payload['show_on_corner_page'];
        $attributes['sports'] = array_values($payload['sports']);
        $attributes['merged_into_id'] = null;

        return $attributes;
    }

    private function resolveImagePath(Club $duplicate, Club $survivor, string $imageSource): ?string
    {
        return match ($imageSource) {
            'duplicate' => $duplicate->image_path,
            'none' => null,
            default => $survivor->image_path,
        };
    }

    /**
     * @param  array<string, 'survivor'|'duplicate'>  $sources
     */
    private function remapCornerTranslations(Club $duplicate, Club $survivor, array $sources): void
    {
        $clubType = (new Club)->getMorphClass();

        foreach (self::CORNER_TRANSLATION_COLUMNS as $column) {
            $source = $sources[$column] ?? 'survivor';

            if ($source === 'survivor') {
                Translation::query()
                    ->where('translatable_type', $clubType)
                    ->where('translatable_id', $duplicate->id)
                    ->where('column', $column)
                    ->delete();

                continue;
            }

            Translation::query()
                ->where('translatable_type', $clubType)
                ->where('translatable_id', $survivor->id)
                ->where('column', $column)
                ->delete();

            Translation::query()
                ->where('translatable_type', $clubType)
                ->where('translatable_id', $duplicate->id)
                ->where('column', $column)
                ->update(['translatable_id' => $survivor->id]);
        }

        Translation::query()
            ->where('translatable_type', $clubType)
            ->where('translatable_id', $duplicate->id)
            ->delete();
    }

    private function deleteUnusedImage(?string $path, ?string $keptPath): void
    {
        if ($path === null || $path === '' || $path === $keptPath) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
