<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreSessionChatMessageRequest;
use App\Models\CommunitySession;
use App\Models\User;
use App\Services\Community\SessionChatService;
use App\Support\SessionFeed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The member's session chat inbox with persisted messages and realtime updates.
 */
class SessionChatController extends Controller
{
    public function __construct(private SessionChatService $chat) {}

    public function index(Request $request, string $locale): Response
    {
        $viewer = $request->user();

        return $this->render($viewer, $locale, null);
    }

    public function show(Request $request, string $locale, CommunitySession $communitySession): Response|RedirectResponse
    {
        $this->authorize('chat', $communitySession);

        if (! $this->chat->isWithinRetention($communitySession)) {
            return redirect()->route('member.chat.index', ['locale' => $locale]);
        }

        $viewer = $request->user();
        $this->chat->markRead($viewer, $communitySession);

        $communitySession->load(['host', 'club', 'participants.user', 'participantRecords', 'translations'])
            ->loadCount(['playParticipants', 'joinRequests']);

        return $this->render($viewer, $locale, $communitySession);
    }

    public function store(
        StoreSessionChatMessageRequest $request,
        string $locale,
        CommunitySession $communitySession,
    ): RedirectResponse {
        $this->chat->send(
            $communitySession,
            $request->user(),
            $request->messageBody(),
            $request->images(),
        );

        return back();
    }

    public function markRead(
        Request $request,
        string $locale,
        CommunitySession $communitySession,
    ): RedirectResponse {
        $this->authorize('chat', $communitySession);

        $this->chat->markRead($request->user(), $communitySession);

        return back();
    }

    private function render(User $viewer, string $locale, ?CommunitySession $active): Response
    {
        $sessions = $this->chatSessions($viewer);
        $canSend = $active !== null && $this->chat->isOpen($active);
        $purgeAt = $active !== null ? $this->chat->messagesPurgeAt($active) : null;
        $unreadCounts = $this->chat->unreadCountsBySession($viewer, $sessions->pluck('id'));

        return Inertia::render('member/chat', [
            'sessions' => SessionFeed::present($sessions, $viewer, $locale),
            'activeSession' => $active === null
                ? null
                : SessionFeed::toCard($active, $viewer, $locale),
            'viewer' => SessionFeed::player($viewer, $active?->host_id),
            'messages' => $active === null ? [] : $this->chat->listFor($active),
            'can_send' => $canSend,
            'messages_purge_at' => $purgeAt?->toIso8601String(),
            'unread_counts' => $unreadCounts,
        ]);
    }

    /**
     * Sessions the member may open: host or confirmed participant, including
     * past and cancelled while still inside the 7-day retention window.
     *
     * @return Collection<int, CommunitySession>
     */
    private function chatSessions(User $viewer): Collection
    {
        $query = SessionFeed::base()
            ->where(function (Builder $query) use ($viewer): void {
                $query->where('host_id', $viewer->id)
                    ->orWhereHas(
                        'participants',
                        fn (Builder $participant) => $participant->where('user_id', $viewer->id),
                    );
            });

        return $this->chat->scopeWithinRetention($query)
            ->orderByDesc('starts_at')
            ->get();
    }
}
