<?php

namespace App\Support;

use App\Models\CommunityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The member-facing events tabs mirror the session tabs. The events data model
 * is unchanged — only the reading shape lives here.
 */
final class EventFeed
{
    public const PER_PAGE = 9;

    public const TABS = ['open', 'mine', 'past'];

    /**
     * @return Builder<CommunityEvent>
     */
    public static function open(): Builder
    {
        return self::base()->where('starts_at', '>', now())->orderBy('starts_at');
    }

    /**
     * @return Builder<CommunityEvent>
     */
    public static function mine(User $user): Builder
    {
        return self::base()
            ->where('starts_at', '>', now())
            ->whereHas('rsvps', fn (Builder $rsvps) => $rsvps->where('user_id', $user->id))
            ->orderBy('starts_at');
    }

    /**
     * @return Builder<CommunityEvent>
     */
    public static function past(User $user): Builder
    {
        return self::base()
            ->where('starts_at', '<=', now())
            ->whereHas('rsvps', fn (Builder $rsvps) => $rsvps->where('user_id', $user->id))
            ->orderByDesc('starts_at');
    }

    /**
     * @return Builder<CommunityEvent>
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
     * @return Builder<CommunityEvent>
     */
    public static function base(): Builder
    {
        return CommunityEvent::query()
            ->with(['rsvps.user', 'translations'])
            ->withCount('rsvps');
    }

    /**
     * @return array<string, mixed>
     */
    public static function toCard(CommunityEvent $event, User $viewer, string $locale): array
    {
        $joined = $event->rsvps->contains('user_id', $viewer->id);
        $isPast = $event->starts_at->isPast();

        return [
            'id' => (string) $event->id,
            'title' => $event->translated('title', $locale),
            'description' => $event->translated('description', $locale),
            'location' => $event->translated('location', $locale),
            'starts_at' => $event->starts_at->toIso8601String(),
            'capacity' => $event->capacity,
            'status' => $event->status->value,
            'thumbnail_url' => $event->thumbnailUrl(),
            'rsvp_count' => (int) $event->rsvps_count,
            'attendees' => $event->rsvps
                ->take(4)
                ->map(fn ($rsvp): array => SessionFeed::player($rsvp->user))
                ->values()
                ->all(),
            'is_full' => $event->isFull(),
            'is_past' => $isPast,
            'is_joined' => $joined,
            'can_join' => ! $joined && ! $isPast && ! $event->isFull(),
            'can_leave' => $joined && ! $isPast,
        ];
    }
}
