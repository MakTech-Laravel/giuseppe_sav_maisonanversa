<?php

namespace App\Policies;

use App\Models\CommunitySession;
use App\Models\User;

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
     */
    public function leave(User $user, CommunitySession $session): bool
    {
        return ! $session->isHostedBy($user) && ! $session->isPast();
    }

    public function join(User $user, CommunitySession $session): bool
    {
        return ! $session->isPast()
            && ! $session->isCancelled()
            && ! $session->isFull();
    }
}
