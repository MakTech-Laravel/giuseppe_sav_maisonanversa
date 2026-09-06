<?php

namespace App\Support;

use App\Models\CommunitySession;
use App\Models\EventRsvp;
use App\Models\User;

/**
 * Read-only member activity passport — separate from the Heritage artefact.
 */
class MemberPassportPresenter
{
    public function __construct(private PassportPresenter $heritage) {}

    /**
     * @return array{
     *     membership: array{status: string, status_label: string, edition_number: string|null},
     *     badges: list<array{id: string, label: string}>,
     *     sessions: array{
     *         upcoming: list<array<string, mixed>>,
     *         hosted_count: int,
     *         joined_count: int
     *     },
     *     events: array{
     *         upcoming: list<array<string, mixed>>,
     *         past_count: int
     *     }
     * }
     */
    public function forUser(User $user, string $locale): array
    {
        $heritageOrder = $this->heritage->heritageOrder($user);
        $approvedClaim = $this->heritage->approvedClaim($user);
        $editionNumber = $heritageOrder?->edition_number !== null
            ? str_pad((string) $heritageOrder->edition_number, 3, '0', STR_PAD_LEFT)
            : ($approvedClaim !== null ? $approvedClaim->paddedEditionNumber() : null);

        $upcomingSessions = SessionFeed::mine($user)
            ->limit(5)
            ->get();

        $upcomingEvents = EventFeed::mine($user)
            ->limit(5)
            ->get();

        return [
            'membership' => [
                'status' => $user->isFoundingCircle() ? 'founding_circle' : 'member',
                'status_label' => $user->isFoundingCircle()
                    ? __('Founding Circle')
                    : __('Lid'),
                'edition_number' => $editionNumber,
            ],
            'badges' => $this->badges($user, $editionNumber !== null),
            'sessions' => [
                'upcoming' => SessionFeed::present($upcomingSessions, $user, $locale),
                'hosted_count' => CommunitySession::query()
                    ->where('host_id', $user->id)
                    ->count(),
                'joined_count' => CommunitySession::query()
                    ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
                    ->count(),
            ],
            'events' => [
                'upcoming' => $upcomingEvents
                    ->map(fn ($event): array => EventFeed::toCard($event, $user, $locale))
                    ->values()
                    ->all(),
                'past_count' => EventFeed::past($user)->count(),
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    private function badges(User $user, bool $hasHeritageEdition): array
    {
        $badges = [];

        if ($user->isFoundingCircle()) {
            $badges[] = [
                'id' => 'founding_circle',
                'label' => __('Founding Circle'),
            ];
        }

        if ($hasHeritageEdition) {
            $badges[] = [
                'id' => 'heritage_holder',
                'label' => __('Heritage-bezitter'),
            ];
        }

        if (CommunitySession::query()->where('host_id', $user->id)->exists()) {
            $badges[] = [
                'id' => 'session_host',
                'label' => __('Sessie-organisator'),
            ];
        }

        if (CommunitySession::query()
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->whereHas('club', fn ($query) => $query->where('is_partner', true))
            ->exists()) {
            $badges[] = [
                'id' => 'partner_club_player',
                'label' => __('Maison Anversa Partner Club'),
            ];
        }

        if (EventRsvp::query()->where('user_id', $user->id)->exists()) {
            $badges[] = [
                'id' => 'event_rsvp',
                'label' => __('Exclusief evenement'),
            ];
        }

        return $badges;
    }
}
