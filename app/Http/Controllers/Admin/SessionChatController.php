<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunitySession;
use App\Models\User;
use App\Support\SessionFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff read-only view of session chats. Messages are not stored yet, so the
 * transcript opens empty; admins never get a composer.
 */
class SessionChatController extends Controller
{
    public function index(Request $request, string $locale): Response
    {
        return $this->render($request->user(), $locale, null);
    }

    public function show(Request $request, string $locale, CommunitySession $communitySession): Response
    {
        $communitySession->load(['host', 'club', 'participants.user', 'participantRecords', 'translations'])
            ->loadCount(['participants', 'joinRequests']);

        return $this->render($request->user(), $locale, $communitySession);
    }

    private function render(User $viewer, string $locale, ?CommunitySession $active): Response
    {
        $sessions = $this->chatSessions();

        return Inertia::render('admin/chat', [
            'sessions' => SessionFeed::present($sessions, $viewer, $locale),
            'activeSession' => $active === null
                ? null
                : SessionFeed::toCard($active, $viewer, $locale),
            'viewer' => SessionFeed::player($viewer, $active?->host_id),
            'messages' => [],
        ]);
    }

    /**
     * Every session with a chat roster: host plus confirmed players, including
     * past and cancelled so staff can still review closed threads.
     *
     * @return Collection<int, CommunitySession>
     */
    private function chatSessions(): Collection
    {
        return SessionFeed::base()
            ->orderByDesc('starts_at')
            ->get();
    }
}
