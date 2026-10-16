<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\CommunitySession;
use App\Models\User;
use App\Support\SessionFeed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The member's session chat inbox. Messages are not stored yet, so the
 * transcript opens empty and lives only in the page's local state.
 */
class SessionChatController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        $viewer = $request->user();

        return $this->render($viewer, $locale, null);
    }

    public function show(Request $request, string $locale, CommunitySession $communitySession): Response
    {
        $this->authorize('chat', $communitySession);

        $viewer = $request->user();

        $communitySession->load(['host', 'club', 'participants.user', 'participantRecords', 'translations'])
            ->loadCount(['participants', 'joinRequests']);

        return $this->render($viewer, $locale, $communitySession);
    }

    private function render(User $viewer, string $locale, ?CommunitySession $active): Response
    {
        $sessions = $this->chatSessions($viewer);

        return Inertia::render('member/chat', [
            'sessions' => SessionFeed::present($sessions, $viewer, $locale),
            'activeSession' => $active === null
                ? null
                : SessionFeed::toCard($active, $viewer, $locale),
            'viewer' => SessionFeed::player($viewer, $active?->host_id),
            'messages' => [],
        ]);
    }

    /**
     * Sessions the member may open: host or confirmed participant, including
     * past and cancelled so closed chats stay readable.
     *
     * @return Collection<int, CommunitySession>
     */
    private function chatSessions(User $viewer): Collection
    {
        return SessionFeed::base()
            ->where(function (Builder $query) use ($viewer): void {
                $query->where('host_id', $viewer->id)
                    ->orWhereHas(
                        'participants',
                        fn (Builder $participant) => $participant->where('user_id', $viewer->id),
                    );
            })
            ->orderByDesc('starts_at')
            ->get();
    }
}
