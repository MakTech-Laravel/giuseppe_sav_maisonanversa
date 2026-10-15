<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreSessionScoreRequest;
use App\Models\CommunitySession;
use App\Models\CommunitySessionScore;
use App\Services\Community\SessionScoreboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Score submission and the host's scoreboard actions. The policy decides
 * who may act; SessionScoreboard rechecks the state under a row lock.
 */
class SessionScoreController extends Controller
{
    public function __construct(private readonly SessionScoreboard $scoreboard) {}

    public function store(StoreSessionScoreRequest $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $data = $request->scoreData();
        $isHost = $communitySession->isHostedBy($request->user());

        $this->scoreboard->submit($communitySession, $request->user(), $data);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isHost
                ? __('De uitslag is opgeslagen.')
                : __('Uw score is ingediend en wacht op goedkeuring van de organisator.'),
        ]);

        return back();
    }

    public function approve(
        Request $request,
        string $locale,
        CommunitySession $communitySession,
        CommunitySessionScore $score,
    ): RedirectResponse {
        $this->authorize('reviewScore', $communitySession);

        $this->scoreboard->approve($communitySession, $score, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Score goedgekeurd.')]);

        return back();
    }

    public function reject(
        Request $request,
        string $locale,
        CommunitySession $communitySession,
        CommunitySessionScore $score,
    ): RedirectResponse {
        $this->authorize('reviewScore', $communitySession);

        $this->scoreboard->reject($communitySession, $score, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Score geweigerd.')]);

        return back();
    }

    public function lock(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('lockScoreboard', $communitySession);

        $this->scoreboard->lock($communitySession);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Scorebord vergrendeld.')]);

        return back();
    }

    public function unlock(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('unlockScoreboard', $communitySession);

        $this->scoreboard->unlock($communitySession);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Scorebord ontgrendeld.')]);

        return back();
    }

    public function finish(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('finish', $communitySession);

        $this->scoreboard->finish($communitySession, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sessie afgesloten.')]);

        return back();
    }
}
