<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunitySession;
use App\Models\User;
use App\Services\Community\SessionChatService;
use App\Support\SessionFeed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff read-only view of session chats. Admins never get a composer.
 */
class SessionChatController extends Controller
{
    public function __construct(private SessionChatService $chat) {}

    public function index(Request $request, string $locale): Response
    {
        return $this->render($request->user(), $locale, null);
    }

    public function show(Request $request, string $locale, CommunitySession $communitySession): Response|RedirectResponse
    {
        if (! $this->chat->isWithinRetention($communitySession)) {
            return redirect()->route('admin.chat.index', ['locale' => $locale]);
        }

        $communitySession->load(['host', 'club', 'participants.user', 'participantRecords', 'translations'])
            ->loadCount(['participants', 'joinRequests']);

        return $this->render($request->user(), $locale, $communitySession);
    }

    private function render(User $viewer, string $locale, ?CommunitySession $active): Response
    {
        $sessions = $this->chatSessions();
        $purgeAt = $active !== null ? $this->chat->messagesPurgeAt($active) : null;

        return Inertia::render('admin/chat', [
            'sessions' => SessionFeed::present($sessions, $viewer, $locale),
            'activeSession' => $active === null
                ? null
                : SessionFeed::toCard($active, $viewer, $locale),
            'viewer' => SessionFeed::player($viewer, $active?->host_id),
            'messages' => $active === null ? [] : $this->chat->listFor($active),
            'can_send' => false,
            'messages_purge_at' => $purgeAt?->toIso8601String(),
        ]);
    }

    /**
     * Every session with a chat roster: host plus confirmed players, including
     * past and cancelled while still inside the 7-day retention window.
     *
     * @return Collection<int, CommunitySession>
     */
    private function chatSessions(): Collection
    {
        return $this->chat->scopeWithinRetention(SessionFeed::base())
            ->orderByDesc('starts_at')
            ->get();
    }
}
