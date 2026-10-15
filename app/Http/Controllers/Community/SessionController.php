<?php

namespace App\Http\Controllers\Community;

use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionParticipantStatus;
use App\Enums\SessionSport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreCommunitySessionRequest;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Services\Community\SessionNotificationService;
use App\Services\Community\SessionScoreboard;
use App\Support\MatchCardPresenter;
use App\Support\SessionFeed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function __construct(
        private readonly SessionNotificationService $notifier,
        private readonly SessionScoreboard $scoreboard,
        private readonly MatchCardPresenter $cards,
    ) {}

    public function index(Request $request, string $locale): Response
    {
        $user = $request->user();
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, SessionFeed::TABS, true) ? $tab : 'open';

        return Inertia::render('maison/sessions/index', [
            'sessions' => Inertia::scroll(
                fn (): LengthAwarePaginator => $this->paginateTab($request, $locale, $tab),
            ),
            'tab' => $tab,
            'filters' => [
                'sport' => $request->string('sport')->toString() ?: null,
                'city' => $request->string('city')->toString() ?: null,
            ],
            'counts' => [
                'open' => SessionFeed::open()->count(),
                'mine' => SessionFeed::mine($user)->count(),
                'past' => SessionFeed::past($user)->count(),
            ],
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginateTab(Request $request, string $locale, string $tab): LengthAwarePaginator
    {
        $user = $request->user();
        $query = SessionFeed::forTab($tab, $user);

        if ($tab === 'open') {
            $sport = $request->string('sport')->toString();

            if (SessionSport::tryFrom($sport) !== null) {
                $query->where('sport', $sport);
            }

            $city = $request->string('city')->toString();

            if ($city !== '') {
                $query->whereHas('club', fn ($club) => $club->where('city', 'like', '%'.$city.'%'));
            }
        }

        $sessions = $query->paginate(SessionFeed::PER_PAGE)
            ->withQueryString()
            ->withPath(route('community.sessions.index', ['locale' => $locale]));

        $sessions->setCollection(
            $sessions->getCollection()->map(
                fn (CommunitySession $session): array => SessionFeed::toCard($session, $user, $locale),
            ),
        );

        return $sessions;
    }

    public function create(Request $request, string $locale): Response
    {
        return Inertia::render('maison/sessions/create', [
            'options' => $this->formOptions(),
            'host' => SessionFeed::player($request->user()),
        ]);
    }

    public function store(StoreCommunitySessionRequest $request, string $locale): RedirectResponse
    {
        $session = DB::transaction(function () use ($request): CommunitySession {
            $session = CommunitySession::query()->create([
                ...$request->validated(),
                'host_id' => $request->user()->id,
            ]);

            CommunitySessionParticipant::query()->create([
                'community_session_id' => $session->id,
                'user_id' => $request->user()->id,
                'status' => SessionParticipantStatus::Confirmed,
                'decided_at' => now(),
            ]);

            return $session;
        });

        $this->notifier->created($session, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie gepubliceerd.')]);

        return redirect()->route('community.sessions.show', [
            'locale' => $locale,
            'communitySession' => $session->id,
        ]);
    }

    public function show(Request $request, string $locale, CommunitySession $communitySession): Response
    {
        $viewer = $request->user();

        $communitySession->load(['host', 'club', 'participants.user', 'participantRecords', 'joinRequests.user', 'translations'])
            ->loadCount(['participants', 'joinRequests']);

        $showsRequests = $viewer !== null
            && $communitySession->isHostedBy($viewer)
            && ! $communitySession->hasStarted()
            && ! $communitySession->isCancelled();

        return Inertia::render('maison/sessions/show', [
            'session' => SessionFeed::toCard($communitySession, $viewer, $locale),
            'joinRequests' => $showsRequests
                ? $communitySession->joinRequests
                    ->sortBy('created_at')
                    ->map(fn (CommunitySessionParticipant $row): array => [
                        ...SessionFeed::player($row->user),
                        'requested_at' => $row->created_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all()
                : [],
            'scoreboard' => $this->scoreboard->present($communitySession, $viewer),
            'matchCard' => Gate::allows('shareCard', $communitySession)
                ? $this->cards->links($communitySession, $locale)
                : null,
            'shareUrl' => $communitySession->isCancelled()
                ? null
                : $communitySession->shareUrl($locale),
        ]);
    }

    public function destroy(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('cancel', $communitySession);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $communitySession->isCancelled()) {
            $communitySession->update([
                'cancelled_at' => now(),
                'cancellation_message' => filled($validated['message'] ?? null) ? trim($validated['message']) : null,
            ]);

            $this->notifier->cancelled($communitySession, $request->user());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie geannuleerd.')]);

        return redirect()->route('community.sessions.index', ['locale' => $locale]);
    }

    /**
     * Joining is a request: it reserves a seat (confirmed + pending never
     * exceeds capacity) and waits for the host's decision.
     */
    public function join(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $requested = DB::transaction(function () use ($request, $communitySession): bool {
            $locked = CommunitySession::query()
                ->whereKey($communitySession->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = CommunitySessionParticipant::query()->where([
                'community_session_id' => $locked->id,
                'user_id' => $request->user()->id,
            ])->first();

            if ($existing !== null) {
                abort_if(
                    $existing->status === SessionParticipantStatus::Rejected,
                    422,
                    __('De organisator heeft uw aanvraag voor deze sessie niet aanvaard.'),
                );

                return false;
            }

            abort_if($locked->isCancelled(), 422, __('Deze sessie is geannuleerd.'));
            abort_if($locked->isPast(), 422, __('Deze sessie is al voorbij.'));
            abort_if($locked->isRegistrationClosed(), 422, __('De inschrijvingen voor deze sessie zijn gesloten.'));
            abort_if($locked->isFull(), 422, __('Deze sessie is vol.'));
            abort_if(
                $locked->reservedSeats() >= $locked->capacity,
                422,
                __('Alle vrije plaatsen zijn al aangevraagd. Probeer het later opnieuw.'),
            );

            CommunitySessionParticipant::query()->create([
                'community_session_id' => $locked->id,
                'user_id' => $request->user()->id,
                'status' => SessionParticipantStatus::Pending,
            ]);

            return true;
        });

        if ($requested) {
            $this->notifier->requested($communitySession, $request->user());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $requested
                ? __('Uw aanvraag is verstuurd naar de organisator.')
                : __('U heeft deze sessie al aangevraagd.'),
        ]);

        return back();
    }

    public function approveRequest(
        Request $request,
        string $locale,
        CommunitySession $communitySession,
        User $user,
    ): RedirectResponse {
        $this->authorize('decideRequest', $communitySession);

        $approved = DB::transaction(function () use ($communitySession, $user): bool {
            $locked = CommunitySession::query()
                ->whereKey($communitySession->id)
                ->lockForUpdate()
                ->firstOrFail();

            $row = $this->pendingRequest($locked, $user);

            if ($row === null) {
                return false;
            }

            abort_if($locked->isFull(), 422, __('Deze sessie is vol.'));

            $row->update([
                'status' => SessionParticipantStatus::Confirmed,
                'decided_at' => now(),
            ]);

            return true;
        });

        if ($approved) {
            $this->notifier->approved($communitySession, $user, $request->user());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Aanvraag goedgekeurd.')]);

        return back();
    }

    public function rejectRequest(
        Request $request,
        string $locale,
        CommunitySession $communitySession,
        User $user,
    ): RedirectResponse {
        $this->authorize('decideRequest', $communitySession);

        $rejected = DB::transaction(function () use ($communitySession, $user): bool {
            $locked = CommunitySession::query()
                ->whereKey($communitySession->id)
                ->lockForUpdate()
                ->firstOrFail();

            $row = $this->pendingRequest($locked, $user);

            if ($row === null) {
                return false;
            }

            $row->update([
                'status' => SessionParticipantStatus::Rejected,
                'decided_at' => now(),
            ]);

            return true;
        });

        if ($rejected) {
            $this->notifier->rejected($communitySession, $user, $request->user());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Aanvraag geweigerd.')]);

        return back();
    }

    /**
     * Leaves a confirmed seat or withdraws a pending request. A rejected row
     * stays, so a turned-down member cannot ask again.
     */
    public function leave(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('leave', $communitySession);

        $row = CommunitySessionParticipant::query()
            ->where('community_session_id', $communitySession->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', SessionParticipantStatus::seatHolding())
            ->first();

        $wasPending = $row?->isPending() ?? false;
        $wasFull = $communitySession->participants()->count() >= $communitySession->capacity;

        $left = $row === null
            ? 0
            : CommunitySessionParticipant::query()->whereKey($row->id)->delete();

        if ($left > 0) {
            if ($wasPending) {
                $this->notifier->withdrawn($communitySession, $request->user());
            } else {
                $this->notifier->left($communitySession, $request->user(), $wasFull);
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $wasPending ? __('Uw aanvraag is ingetrokken.') : __('U heeft de sessie verlaten.'),
        ]);

        return back();
    }

    public function removeParticipant(
        Request $request,
        string $locale,
        CommunitySession $communitySession,
        User $user,
    ): RedirectResponse {
        $this->authorize('removeParticipant', $communitySession);

        abort_if(
            $communitySession->isHostedBy($user),
            422,
            __('De host kan niet uit de eigen sessie worden verwijderd.'),
        );

        $wasFull = $communitySession->participants()->count() >= $communitySession->capacity;

        $removed = CommunitySessionParticipant::query()
            ->where('community_session_id', $communitySession->id)
            ->where('user_id', $user->id)
            ->where('status', SessionParticipantStatus::Confirmed->value)
            ->delete();

        if ($removed > 0) {
            $this->notifier->removed($communitySession, $user, $request->user(), $wasFull);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Speler verwijderd uit de sessie.')]);

        return back();
    }

    private function pendingRequest(CommunitySession $session, User $user): ?CommunitySessionParticipant
    {
        return CommunitySessionParticipant::query()
            ->where('community_session_id', $session->id)
            ->where('user_id', $user->id)
            ->where('status', SessionParticipantStatus::Pending->value)
            ->lockForUpdate()
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'sports' => SessionSport::options(),
            'levels' => SessionLevel::options(),
            'genders' => SessionGender::options(),
            'court_statuses' => SessionCourtStatus::options(),
            'durations' => array_map(
                fn (int $minutes): array => ['value' => $minutes, 'label' => $minutes.' min'],
                CommunitySession::DURATION_MINUTES,
            ),
            'capacities' => [2, 4, 6, 8],
            'partner_clubs' => Club::query()
                ->sessionVenues()
                ->where('is_partner', true)
                ->orderBy('name')
                ->limit(6)
                ->get()
                ->map(fn (Club $club): array => $club->toCardArray())
                ->all(),
        ];
    }
}
