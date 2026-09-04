<?php

namespace App\Support;

use App\Enums\SessionSport;
use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Approved venue directory for the member community.
 */
final class ClubDirectory
{
    public const PER_PAGE = 12;

    /**
     * @return Builder<Club>
     */
    public static function query(?string $search = null, ?string $city = null, ?string $sport = null): Builder
    {
        return Club::query()
            ->approved()
            ->when(filled(trim((string) $search)), fn (Builder $query) => $query->matching($search))
            ->when(filled(trim((string) $city)), fn (Builder $query) => $query->where('city', 'like', '%'.trim($city).'%'))
            ->when(
                SessionSport::tryFrom((string) $sport) !== null,
                fn (Builder $query) => $query->whereJsonContains('sports', $sport),
            )
            ->orderByDesc('is_partner')
            ->orderBy('name');
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public static function paginate(?string $search, ?string $city, ?string $sport, string $locale): LengthAwarePaginator
    {
        return self::query($search, $city, $sport)
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Club $club): array => self::toCard($club));
    }

    /**
     * @return array<string, mixed>
     */
    public static function toCard(Club $club): array
    {
        return [
            ...$club->toCardArray(),
            'slug' => $club->slug,
            'website' => $club->website,
            'phone' => $club->phone,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function toProfile(Club $club, User $viewer, string $locale): array
    {
        $upcoming = SessionFeed::open()
            ->where('club_id', $club->id)
            ->limit(6)
            ->get();

        return [
            ...self::toCard($club),
            'upcoming_sessions' => SessionFeed::present($upcoming, $viewer, $locale),
            'upcoming_sessions_count' => SessionFeed::open()->where('club_id', $club->id)->count(),
        ];
    }
}
