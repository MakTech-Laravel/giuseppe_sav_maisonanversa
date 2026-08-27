<?php

namespace App\Support;

use App\Models\CommunitySession;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Queries and presentation for the three session tabs.
 *
 * "Past" always compares the stored `ends_at` column rather than deriving an
 * end from `starts_at`, so the filter stays an indexed comparison.
 */
final class SessionFeed
{
    public const PER_PAGE = 9;

    public const TABS = ['open', 'mine', 'past'];

    /**
     * Open sessions anyone may still see: upcoming, not cancelled.
     *
     * @return Builder<CommunitySession>
     */
    public static function open(): Builder
    {
        return self::base()->upcoming()->orderBy('starts_at');
    }

    /**
     * Sessions the member hosts or has joined that have not ended yet.
     *
     * @return Builder<CommunitySession>
     */
    public static function mine(User $user): Builder
    {
        return self::base()
            ->whereNull('cancelled_at')
            ->where('ends_at', '>', now())
            ->where(function (Builder $query) use ($user): void {
                $query->where('host_id', $user->id)
                    ->orWhereHas('participants', fn (Builder $p) => $p->where('user_id', $user->id));
            })
            ->orderBy('starts_at');
    }

    /**
     * Ended sessions the member actually took part in.
     *
     * @return Builder<CommunitySession>
     */
    public static function past(User $user): Builder
    {
        return self::base()
            ->past()
            ->whereHas('participants', fn (Builder $p) => $p->where('user_id', $user->id))
            ->orderByDesc('starts_at');
    }

    /**
     * @return Builder<CommunitySession>
     */
    public static function forTab(string $tab, User $user): Builder
    {
        return match ($tab) {
            'mine' => self::mine($user),
            'past' => self::past($user),
            default => self::open(),
        };
    }

    /**
     * @return Builder<CommunitySession>
     */
    public static function base(): Builder
    {
        return CommunitySession::query()
            ->with(['host', 'club', 'participants.user'])
            ->withCount('participants');
    }

    /**
     * @param  Collection<int, CommunitySession>  $sessions
     * @return list<array<string, mixed>>
     */
    public static function present(Collection $sessions, User $viewer): array
    {
        return $sessions
            ->map(fn (CommunitySession $session): array => self::toCard($session, $viewer))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function toCard(CommunitySession $session, User $viewer): array
    {
        $players = $session->participants
            ->map(fn ($participant): array => self::player($participant->user, $session->host_id))
            ->values()
            ->all();

        $joined = $session->participants->contains('user_id', $viewer->id);
        $isHost = $session->host_id === $viewer->id;
        $isPast = $session->isPast();

        return [
            'id' => (string) $session->id,
            'sport' => $session->sport->value,
            'sport_label' => $session->sport->label(),
            'club' => $session->club?->toCardArray(),
            'starts_at' => $session->starts_at->toIso8601String(),
            'ends_at' => $session->ends_at?->toIso8601String(),
            'duration_minutes' => $session->duration_minutes,
            'court_status' => $session->court_status->value,
            'court_status_label' => $session->court_status->label(),
            'level' => $session->level->value,
            'level_label' => $session->level->label(),
            'gender' => $session->gender->value,
            'gender_label' => $session->gender->label(),
            'capacity' => $session->capacity,
            'participants_count' => $session->participantsCount(),
            'open_slots' => $session->openSlots(),
            'players' => $players,
            'notes' => $session->notes,
            'host' => self::player($session->host, $session->host_id),
            'is_full' => $session->isFull(),
            'is_past' => $isPast,
            'is_cancelled' => $session->isCancelled(),
            'is_host' => $isHost,
            'is_joined' => $joined,
            'can_join' => ! $joined && ! $isPast && ! $session->isCancelled() && ! $session->isFull(),
            'can_leave' => $joined && ! $isHost && ! $isPast,
            'can_manage' => $isHost && ! $isPast,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function player(User $user, ?int $hostId = null): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'initials' => mb_strtoupper(mb_substr($user->name, 0, 2)),
            'avatar_url' => $user->avatar ? Storage::disk('public')->url($user->avatar) : null,
            'is_host' => $hostId !== null && $user->id === $hostId,
        ];
    }
}
