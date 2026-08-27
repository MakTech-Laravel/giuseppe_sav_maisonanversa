<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SessionSport;
use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admins observe and moderate sessions; they never host one, so there is no
 * create/edit pair here. Every lifecycle state stays visible.
 */
class CommunitySessionController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request, string $locale): Response
    {
        $search = $request->string('search')->toString();
        $sport = $request->string('sport')->toString();
        $status = $request->string('status')->toString();
        $clubId = $request->integer('club_id');

        $sessions = CommunitySession::query()
            ->with(['host', 'club'])
            ->withCount('participants')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function (Builder $inner) use ($like): void {
                    $inner->whereHas('host', fn (Builder $host) => $host->where('name', 'like', $like))
                        ->orWhereHas('club', fn (Builder $club) => $club->where('name', 'like', $like)
                            ->orWhere('city', 'like', $like));
                });
            })
            ->when(SessionSport::tryFrom($sport) !== null, fn (Builder $query) => $query->where('sport', $sport))
            ->when($clubId > 0, fn (Builder $query) => $query->where('club_id', $clubId))
            ->when($status === 'active', fn (Builder $query) => $query->upcoming())
            ->when($status === 'ended', fn (Builder $query) => $query->whereNull('cancelled_at')->past())
            ->when($status === 'cancelled', fn (Builder $query) => $query->whereNotNull('cancelled_at'))
            ->orderByDesc('starts_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (CommunitySession $session): array => $this->row($session));

        return Inertia::render('admin/community-sessions/index', [
            'sessions' => $sessions,
            'filters' => [
                'search' => $search ?: null,
                'sport' => $sport ?: null,
                'status' => $status ?: null,
                'club_id' => $clubId > 0 ? $clubId : null,
            ],
            'sportOptions' => SessionSport::options(),
            'clubOptions' => $this->clubOptions(),
        ]);
    }

    public function show(Request $request, string $locale, CommunitySession $communitySession): Response
    {
        $communitySession->load(['host', 'club', 'participants.user', 'translations'])->loadCount('participants');

        return Inertia::render('admin/community-sessions/show', [
            'session' => [
                ...$this->row($communitySession),
                'court_status_label' => $communitySession->court_status->label(),
                'gender_label' => $communitySession->gender->label(),
                'duration_minutes' => $communitySession->duration_minutes,
                'ends_at' => $communitySession->ends_at?->toIso8601String(),
                'notes' => $communitySession->translated('notes'),
                'club' => $communitySession->club?->toCardArray(),
                'host_id' => $communitySession->host_id,
                'players' => $communitySession->participants
                    ->map(fn (CommunitySessionParticipant $participant): array => [
                        'id' => $participant->user->id,
                        'name' => $participant->user->name,
                        'email' => $participant->user->email,
                        'is_host' => $participant->user_id === $communitySession->host_id,
                        'joined_at' => $participant->created_at?->toIso8601String(),
                    ])->values()->all(),
            ],
        ]);
    }

    public function cancel(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $communitySession->update(['cancelled_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie geannuleerd.')]);

        return back();
    }

    public function removeParticipant(
        Request $request,
        string $locale,
        CommunitySession $communitySession,
        User $user,
    ): RedirectResponse {
        abort_if(
            $communitySession->isHostedBy($user),
            422,
            __('De host kan niet uit de eigen sessie worden verwijderd.'),
        );

        CommunitySessionParticipant::query()
            ->where('community_session_id', $communitySession->id)
            ->where('user_id', $user->id)
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Speler verwijderd uit de sessie.')]);

        return back();
    }

    public function destroy(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $communitySession->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie verwijderd.')]);

        return redirect()->route('admin.community-sessions.index', ['locale' => $locale]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(CommunitySession $session): array
    {
        return [
            'id' => (string) $session->id,
            'host' => $session->host->name,
            'sport' => $session->sport->value,
            'sport_label' => $session->sport->label(),
            'club_id' => $session->club_id,
            'club_name' => $session->club?->name,
            'club_city' => $session->club?->city,
            'starts_at' => $session->starts_at->toIso8601String(),
            'capacity' => $session->capacity,
            'participants_count' => $session->participantsCount(),
            'level_label' => $session->level->label(),
            'lifecycle' => match (true) {
                $session->isCancelled() => 'cancelled',
                $session->isPast() => 'ended',
                default => 'active',
            },
        ];
    }

    /**
     * Clubs are only needed when the index renders a club filter.
     *
     * @return list<array{id: int, name: string}>
     */
    private function clubOptions(): array
    {
        return Club::query()
            ->approved()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Club $club): array => ['id' => $club->id, 'name' => $club->name])
            ->all();
    }
}
