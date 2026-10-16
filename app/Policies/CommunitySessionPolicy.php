<?php

namespace App\Policies;

use App\Models\CommunitySession;
use App\Models\User;
use App\Services\Community\SessionChatService;

class CommunitySessionPolicy
{
    /**
     * Cancelling belongs to the host; admins can step in for moderation.
     */
    public function cancel(User $user, CommunitySession $session): bool
    {
        return $session->isHostedBy($user) || $user->isAdmin();
    }

    /**
     * Only the host removes players, and only while the session is still ahead.
     */
    public function removeParticipant(User $user, CommunitySession $session): bool
    {
        return ! $session->isPast() && ($session->isHostedBy($user) || $user->isAdmin());
    }

    /**
     * The host holds slot one and cannot walk away — they cancel instead.
     * Leaving also withdraws a request that is still pending. Players may
     * leave until the start, even after registration has closed.
     */
    public function leave(User $user, CommunitySession $session): bool
    {
        return ! $session->isHostedBy($user) && ! $session->hasStarted();
    }

    public function join(User $user, CommunitySession $session): bool
    {
        return $session->acceptsRequests()
            && ! $session->participantRecords()->where('user_id', $user->id)->exists();
    }

    /**
     * Only the host approves or rejects join requests. Requests sent before
     * the registration deadline can still be decided until the start.
     */
    public function decideRequest(User $user, CommunitySession $session): bool
    {
        return $session->isHostedBy($user)
            && ! $session->hasStarted()
            && ! $session->isCancelled();
    }

    /**
     * Confirmed players submit while the session is held and unlocked. Other
     * players only inside the score window; the host until they close it.
     */
    public function submitScore(User $user, CommunitySession $session): bool
    {
        if (! $session->isHeld() || $session->isScoreboardLocked()) {
            return false;
        }

        $isPlayer = $session->participants()->where('user_id', $user->id)->exists();

        if (! $isPlayer) {
            return false;
        }

        return $session->isHostedBy($user) || $session->isScoreWindowOpen();
    }

    /**
     * The match card (preview, share, download) belongs to the organiser and
     * the confirmed players. Everyone else only sees the roster and a link.
     */
    public function shareCard(?User $user, CommunitySession $session): bool
    {
        if ($user === null || $session->isCancelled()) {
            return false;
        }

        return $session->isHostedBy($user)
            || $session->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * The session chat belongs to the host and the confirmed players. It
     * stays readable after the session closes; only posting stops.
     */
    public function chat(User $user, CommunitySession $session): bool
    {
        return $session->isHostedBy($user)
            || $session->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Posting requires a chat roster seat while the session is still open.
     */
    public function sendMessage(User $user, CommunitySession $session): bool
    {
        return $this->chat($user, $session)
            && app(SessionChatService::class)->isOpen($session);
    }

    public function reviewScore(User $user, CommunitySession $session): bool
    {
        return $this->managesScoreboard($user, $session);
    }

    public function lockScoreboard(User $user, CommunitySession $session): bool
    {
        return $this->managesScoreboard($user, $session);
    }

    public function unlockScoreboard(User $user, CommunitySession $session): bool
    {
        return $this->managesScoreboard($user, $session);
    }

    public function finish(User $user, CommunitySession $session): bool
    {
        return $this->managesScoreboard($user, $session);
    }

    /**
     * The scoreboard belongs to the host until the session is finished.
     */
    private function managesScoreboard(User $user, CommunitySession $session): bool
    {
        return $session->isHostedBy($user) && $session->isHeld();
    }
}
