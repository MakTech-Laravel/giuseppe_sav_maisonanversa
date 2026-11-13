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
                ? __('The result has been saved.')
                : __('Your score has been submitted and is awaiting the organiser’s approval.'),
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Score approved.')]);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Score declined.')]);

        return back();
    }

    public function lock(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('lockScoreboard', $communitySession);

        $this->scoreboard->lock($communitySession);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Scoreboard locked.')]);

        return back();
    }

    public function unlock(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('unlockScoreboard', $communitySession);

        $this->scoreboard->unlock($communitySession);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Scoreboard unlocked.')]);

        return back();
    }

    public function finish(Request $request, string $locale, CommunitySession $communitySession): RedirectResponse
    {
        $this->authorize('finish', $communitySession);

        $this->scoreboard->finish($communitySession, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Session closed.')]);

        return back();
    }
}
