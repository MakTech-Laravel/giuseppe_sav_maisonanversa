<?php

namespace App\Support;

use App\Enums\SessionParticipantStatus;
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
     * Open sessions that still have a free seat and still take requests
     * (homepage joinable list).
     *
     * @return Builder<CommunitySession>
     */
    public static function joinable(): Builder
    {
        return self::open()
            ->where('starts_at', '>', now()->addHours(CommunitySession::REGISTRATION_CLOSES_HOURS))
            ->whereRaw(
                "(select count(*) from community_session_participants where community_session_participants.community_session_id = community_sessions.id and community_session_participants.status in ('pending', 'confirmed') and community_session_participants.for_chat_only = 0) < community_sessions.capacity",
            );
    }

    /**
     * Sessions the member hosts, plays in or has asked to join, that have
     * not ended yet.
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
                    ->orWhereHas('participantRecords', fn (Builder $p) => $p
                        ->where('user_id', $user->id)
                        ->whereIn('status', SessionParticipantStatus::seatHolding()));
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
            ->with(['host', 'club', 'participants.user', 'participantRecords', 'translations'])
            ->withCount(['playParticipants', 'joinRequests']);
    }

    /**
     * @param  Collection<int, CommunitySession>  $sessions
     * @return list<array<string, mixed>>
     */
    public static function present(Collection $sessions, ?User $viewer, string $locale): array
    {
        return $sessions
            ->map(fn (CommunitySession $session): array => self::toCard($session, $viewer, $locale))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function toCard(CommunitySession $session, ?User $viewer, string $locale): array
    {
        $redactNames = $viewer === null;

        $playParticipants = $session->participants
            ->where('for_chat_only', false)
            ->values();

        $players = $playParticipants
            ->map(fn ($participant): array => self::player(
                $participant->user,
                $session->host_id,
                $redactNames,
            ))
            ->values()
            ->all();

        $joined = $viewer !== null
            && $playParticipants->contains('user_id', $viewer->id);
        $isHost = $viewer !== null && $session->host_id === $viewer->id;
        $isPast = $session->isPast();
        $hasStarted = $session->hasStarted();
        $viewerStatus = $viewer === null
            ? null
            : $session->participantRecords->firstWhere('user_id', $viewer->id)?->status;
        $isPending = $viewerStatus === SessionParticipantStatus::Pending;
        $lifecycle = $session->lifecycle();
        $waitingCount = $isPast || $session->isCancelled() ? 0 : $session->pendingCount();

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
            'requests_required' => $session->requiresJoinRequests(),
            'participants_count' => $session->participantsCount(),
            'open_slots' => max(0, $session->capacity - $session->participantsCount() - $waitingCount),
            'players' => $players,
            'notes' => $session->translated('notes', $locale),
            'host' => self::player($session->host, $session->host_id, $redactNames),
            'is_full' => $session->isFull(),
            'is_past' => $isPast,
            'is_cancelled' => $session->isCancelled(),
            'is_host' => $isHost,
            'is_joined' => $joined,
            'can_join' => $viewerStatus === null && $session->acceptsRequests(),
            'can_leave' => $joined && ! $isHost && ! $hasStarted,
            'can_manage' => $isHost && ! $isPast,
            'pending_count' => $waitingCount,
            'viewer_status' => $viewerStatus?->value,
            'is_pending' => $isPending,
            'can_withdraw' => $isPending && ! $hasStarted,
            'registration_closed' => ! $isPast && ! $session->isCancelled() && $session->isRegistrationClosed(),
            'registration_closes_at' => $session->registrationClosesAt()->toIso8601String(),
            'lifecycle' => $lifecycle->value,
            'lifecycle_label' => $lifecycle->label(),
            'is_locked' => $session->isScoreboardLocked(),
            'share_url' => $session->isCancelled() ? null : $session->shareUrl($locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function player(User $user, ?int $hostId = null, bool $redactName = false): array
    {
        return [
            'id' => $user->id,
            'name' => $redactName ? '' : $user->name,
            'initials' => mb_strtoupper(mb_substr($user->name, 0, 2)),
            'avatar_url' => $redactName || ! $user->avatar
                ? null
                : Storage::disk('public')->url($user->avatar),
            'is_host' => $hostId !== null && $user->id === $hostId,
        ];
    }
}
