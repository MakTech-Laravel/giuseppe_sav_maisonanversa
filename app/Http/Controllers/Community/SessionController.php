<?php

namespace App\Http\Controllers\Community;

use App\Enums\SessionCourtStatus;
use App\Enums\SessionGender;
use App\Enums\SessionLevel;
use App\Enums\SessionSport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreCommunitySessionRequest;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Notifications\SessionJoinedNotification;
use App\Support\SessionFeed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
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
            ]);

            return $session;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie gepubliceerd.')]);

        return redirect()->route('community.sessions.show', [
            'locale' => $locale,
            'communitySession' => $session->id,
        ]);
    }

    public function show(Request $request, string $locale, CommunitySession $communitySession): Response
    {
        $communitySession->load(['host', 'club', 'participants.user', 'translations'])->loadCount('participants');

        return Inertia::render('maison/sessions/show', [
            'session' => SessionFeed::toCard($communitySession, $request->user(), $locale),
        ]);
    }

    public function destroy(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('cancel', $communitySession);

        $communitySession->update(['cancelled_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie geannuleerd.')]);

        return redirect()->route('community.sessions.index', ['locale' => $locale]);
    }

    public function join(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        DB::transaction(function () use ($request, $communitySession): void {
            $locked = CommunitySession::query()
                ->whereKey($communitySession->id)
                ->lockForUpdate()
                ->firstOrFail();

            $already = CommunitySessionParticipant::query()->where([
                'community_session_id' => $locked->id,
                'user_id' => $request->user()->id,
            ])->exists();

            if ($already) {
                return;
            }

            abort_if($locked->isCancelled(), 422, __('Deze sessie is geannuleerd.'));
            abort_if($locked->isPast(), 422, __('Deze sessie is al voorbij.'));
            abort_if($locked->isFull(), 422, __('Deze sessie is vol.'));

            CommunitySessionParticipant::query()->create([
                'community_session_id' => $locked->id,
                'user_id' => $request->user()->id,
            ]);

            $locked->host->notify(new SessionJoinedNotification($locked, $request->user()->name));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('U bent aangemeld voor de sessie.')]);

        return back();
    }

    public function leave(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('leave', $communitySession);

        CommunitySessionParticipant::query()
            ->where('community_session_id', $communitySession->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('U heeft de sessie verlaten.')]);

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

        CommunitySessionParticipant::query()
            ->where('community_session_id', $communitySession->id)
            ->where('user_id', $user->id)
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Speler verwijderd uit de sessie.')]);

        return back();
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
            'durations' => [
                ['value' => 60, 'label' => '60 min'],
                ['value' => 90, 'label' => '90 min'],
                ['value' => 120, 'label' => '120 min'],
                ['value' => 150, 'label' => '150 min'],
                ['value' => 180, 'label' => '180 min'],
            ],
            'capacities' => [2, 4, 6, 8],
            'partner_clubs' => Club::query()
                ->approved()
                ->where('is_partner', true)
                ->orderBy('name')
                ->limit(6)
                ->get()
                ->map(fn (Club $club): array => $club->toCardArray())
                ->all(),
        ];
    }
}
